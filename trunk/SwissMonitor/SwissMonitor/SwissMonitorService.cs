using System;
using System.Diagnostics;
using System.ServiceProcess;
using System.Threading;
using MySql.Data.MySqlClient;

namespace Swiss.Monitor
{
    partial class SwissMonitorService : ServiceBase
    {
        private Thread _backgroundThread = null;
        private ManualResetEvent _shutdownEvent = new ManualResetEvent(false);

        private Timer _housekeepingTimer;
        private bool _isDoingHousekeeping = false;

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
            Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.SERVICE_STARTING, "Service starting");

            // Housekeeping is disabled for now as nothing uses it, uncomment this line if needed later
            //_housekeepingTimer = new Timer(DoHousekeeping, null, TimeSpan.FromSeconds(30), TimeSpan.FromHours(1));

            _backgroundThread = new Thread(WorkerThread);
            _backgroundThread.IsBackground = true;
            _backgroundThread.Start();

            Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.SERVICE_STARTED,
                                              "Service started");
        }

        protected override void OnStop()
        {
            try
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.SERVICE_STOP_REQUEST,
                                                  "Service stop request received");
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
            Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.SERVICE_STOPPING, "Service stopping");

            if (_housekeepingTimer != null)
            {
                _housekeepingTimer.Dispose();
                _housekeepingTimer = null;
            }

            _shutdownEvent.Set();

            if(_backgroundThread != null)
                _backgroundThread.Join(10000);

            Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.SERVICE_STOPPED, "Service stopped");
        }

        private void DoHousekeeping(object state)
        {
            // Note the lack of locking around this check, this could potentially cause a race condition
            // but I've ignored it since it's only if the timer interval is so short that another call
            // could come in between the if and the next line, the timer interval is 1 hour so that's unlikely.
            // I'd also like to hope that the housekeeping will not take an hour so this should never be a
            // problem anyway but if so I'd like to trap it and log it.
            if (!_isDoingHousekeeping)
            {
                _isDoingHousekeeping = true;

                // Put any housekeeping tasks here

                _isDoingHousekeeping = false;
            }
            else
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.HOUSEKEEPING_INPROGRESS,
                    "Housekeeping is still running from last time, there could be a problem. Consider restarting the service.");
            }
        }

        private void WorkerThread()
        {
            try
            {
                FileMonitor monitor = new FileMonitor(new SwissCenterNotifier());

                bool isRunning = false;
                int lastDbErrorCode = 0;
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
                        // Ensure that immediately repeating errors only get logged out once
                        if (ex.ErrorCode != lastDbErrorCode)
                        {
                            Tracing.Default.Source.TraceEvent(TraceEventType.Warning,
                                                              (int)Tracing.Events.CANNOT_CONNECT_TO_DB,
                                                              "Unable to connect to database: {0}", ex.Message);

                            Tracing.Default.Source.TraceData(TraceEventType.Verbose,
                                                             (int)Tracing.Events.CANNOT_CONNECT_TO_DB, ex);
                            lastDbErrorCode = ex.ErrorCode;
                        }
                    }
                    catch (Exception ex)
                    {
                        Tracing.Default.Source.TraceEvent(TraceEventType.Critical, (int)Tracing.Events.CANNOT_START_SERVICE,
                                                          "Unable to start service: {0}", ex.Message);

                        Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_START_SERVICE,
                                                         ex);

                        Shutdown();
                    }
                } while (!isRunning && !_shutdownEvent.WaitOne((int)Settings.Default.DatabaseConnectionRetryPeriod.TotalMilliseconds, false));

                
                // Wait for service shutdown
                _shutdownEvent.WaitOne();

                Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.STOPPING_MONITORS, "Stopping monitors");
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
