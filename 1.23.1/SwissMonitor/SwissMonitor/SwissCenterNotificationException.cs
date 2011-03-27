using System;

namespace Swiss.Monitor
{
    [Serializable]
    public class SwissCenterNotificationException : Exception
    {
        public string Result { get; set; }

        public SwissCenterNotificationException(string result)
        {
            Result = result;
        }

        public SwissCenterNotificationException(string message, string result)
            : base(message)
        {
            Result = result;
        }

        public SwissCenterNotificationException(string message, Exception innerException, string result)
            : base(message, innerException)
        {
            Result = result;
        }
    }
}