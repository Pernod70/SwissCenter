using System;
using System.IO;

namespace Swiss.Monitor
{
    public class Change
    {
        public Guid ChangeId { get; private set; }
        public string ItemPath { get; set; }
        public WatcherChangeTypes ChangeType { get; set; }
        public DateTime NextNoficiationAttempt { get; set; }
        public int Retries { get; set; }

        public Change()
        {
            ChangeId = Guid.NewGuid();
            NextNoficiationAttempt = DateTime.UtcNow;
        }

        public override string ToString()
        {
            return string.Format("ID = {0}, ChangeType = {1}, Path = \"{2}\"", ChangeId, ChangeType, ItemPath);
        }
    }
}