using System;
using System.Diagnostics;

namespace Swiss.Monitor
{
    internal class Tracing
    {
        private static readonly Tracing _default = new Tracing();
        
        public enum Events : int
        {
            SERVICE_STARTING = 1,
            SERVICE_STARTED,
            SERVICE_STOPPING,
            SERVICE_STOPPED,
            NOTIFY_SWISSCENTER,
            NOTIFICATION_SENT,
            NOTIFICATION_ERROR,
            NOTIFICATION_RESULTS,
            CONFIGURATION_ERROR,
            WILL_RETRY,
            CHANGE_DETECTED,
            NOTIFICATION_ATTEMPT,
            MAX_RETRIES_REACHED,
            CANNOT_START_SERVICE,
            CANNOT_PROCESS_CHANGE,
            CANNOT_CONNECT_TO_DB,
            STOPPING_MONITORS,
            ERROR_STOPPING_SERVICE,
            ERROR_STARTING_SERVICE,
            INITIALISING_MONITOR,
            MONITOR_ERROR,
            INVALID_LOCATION_PROVIDER,
            UNABLE_TO_READ_SETTING,
            IGNORING_EXTENSION
        }

        public static Tracing Default
        {
            get { return _default; }
        }

        public TraceSource Source { get; private set; }

        public Tracing()
        {
            Source = new TraceSource("Swiss.Monitor");
        }
    }
}
