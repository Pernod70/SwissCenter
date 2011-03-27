namespace Swiss.Monitor
{
    public class NotificationResult
    {
        public enum NotificationStatus
        {
            Ok,
            Failed,
        }

        public NotificationStatus Status { get; set; }

        public string Message { get; set; }

        public bool Retry { get; set; }

        public override string ToString()
        {
            return string.Format("Status = '{0}', Retry = '{1}', Message = '{2}'",
                                 Status, Retry, Message);
        }
    }
}