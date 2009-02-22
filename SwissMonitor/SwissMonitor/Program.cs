using System;
using System.Diagnostics;
using System.ServiceProcess;
using System.Threading;

namespace Swiss.Monitor
{
    static class Program
    {
        /// <summary>
        /// The main entry point for the application.
        /// </summary>
        static void Main()
        {
            if (Debugger.IsAttached)
            {
                EventWaitHandle waitHandle = new EventWaitHandle(false, EventResetMode.AutoReset, "SwissMonitor.WaitHandle");
                SwissMonitorService service = new SwissMonitorService();
                service.Initialize();

                waitHandle.WaitOne();

                service.Shutdown();
            }
            else
            {

                ServiceBase[] ServicesToRun;
                ServicesToRun = new ServiceBase[]
                                {
                                    new SwissMonitorService()
                                };
                ServiceBase.Run(ServicesToRun);
            }
        }
    }
}
