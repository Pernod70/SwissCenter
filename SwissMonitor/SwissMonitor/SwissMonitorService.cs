using System;
using System.Collections;
using System.Diagnostics;
using System.IO;
using System.ServiceProcess;
using System.Threading;
using MySql.Data.MySqlClient;

namespace Swiss.Monitor
{
    partial class SwissMonitorService : ServiceBase
    {
        private Thread _backgroundThread = null;
        private ManualResetEvent _shutdownEvent = new ManualResetEvent(false);

        public SwissMonitorService()
        {
            InitializeComponent();
        }

        protected override void OnStart(string[] args)
        {
            try
            {
                Initialize();
            }
            catch (Exception ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.ERROR_STARTING_SERVICE,
                    "Error starting service: {0}", ex.Message);

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.ERROR_STARTING_SERVICE, ex);
            }
        }

        public void Initialize()
        {
            Tracing.Default.Source.TraceEvent(TraceEventType.Start, (int)Tracing.Events.SERVICE_STARTING, "Service starting");

            _backgroundThread = new Thread(WorkerThread);
            _backgroundThread.IsBackground = true;
            _backgroundThread.Start();

            Tracing.Default.Source.TraceEvent(TraceEventType.Start, (int)Tracing.Events.SERVICE_STARTED,
                                              "Service started");
        }

        protected override void OnStop()
        {
            try
            {
                Shutdown();
            }
            catch(Exception ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.ERROR_STOPPING_SERVICE,
                    "Error stopping service: {0}", ex.Message);

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.ERROR_STOPPING_SERVICE, ex);
            }
        }

        public void Shutdown()
        {
            Tracing.Default.Source.TraceEvent(TraceEventType.Stop, (int)Tracing.Events.SERVICE_STOPPING, "Service stopping");

            _shutdownEvent.Set();

            if(_backgroundThread != null)
                _backgroundThread.Join(10000);
            
            Tracing.Default.Source.TraceEvent(TraceEventType.Stop, (int)Tracing.Events.SERVICE_STOPPED, "Service stopped");
        }


        private void WorkerThread()
        {
            try
            {
                FileMonitor monitor = new FileMonitor(new SwissCenterNotifier());

                bool isRunning = false;
                do
                {
                    IMonitorLocations locations = LocationFactory.CreateLocations();

                    try
                    {
                        foreach (string location in locations.GetLocations())
                        {
                            Tracing.Default.Source.TraceInformation("Configuring monitor for {0}", location);

                            monitor.AddMonitor(location);
                        }

                        monitor.Initialize();

                        isRunning = true;
                    }
                    catch (MySqlException ex)
                    {
                        Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.CANNOT_CONNECT_TO_DB,
                                                          "Unable to connect to database: {0}", ex.Message);

                        Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_CONNECT_TO_DB, ex);
                    }
                    catch (Exception ex)
                    {
                        Tracing.Default.Source.TraceEvent(TraceEventType.Critical, (int)Tracing.Events.CANNOT_START_SERVICE,
                                                          "Unable to start service: {0}", ex.Message);

                        Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_START_SERVICE,
                                                         ex);

                        Shutdown();
                    }
                } while (!isRunning && !_shutdownEvent.WaitOne(Settings.Default.DatabaseConnectionRetryPeriod, false));

                
                // Wait for service shutdown
                _shutdownEvent.WaitOne();

                Tracing.Default.Source.TraceEvent(TraceEventType.Stop, (int)Tracing.Events.STOPPING_MONITORS, "Stopping monitors");
                monitor.Shutdown();
            }
            catch (Exception ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Critical, (int)Tracing.Events.CANNOT_START_SERVICE,
                                                  "Unable to start service: {0}", ex.Message);

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_START_SERVICE, ex);
            }
        }
    }
}
