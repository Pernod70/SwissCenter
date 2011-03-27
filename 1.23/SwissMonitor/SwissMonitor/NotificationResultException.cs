using System;

namespace Swiss.Monitor
{
    public class NotificationResultException : Exception
    {
        public NotificationResult Result { get; set; }

        public NotificationResultException(NotificationResult result)
        {
            Result = result;
        }

        public NotificationResultException(string message, NotificationResult result)
            : base(message)
        {
            Result = result;
        }

        public NotificationResultException(string message, Exception innerException, NotificationResult result)
            : base(message, innerException)
        {
            Result = result;
        }
    }
}