using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Threading;

namespace Swiss.Monitor
{
    internal class Notifier : IDisposable
    {
        private readonly INotifier _notifier;
        private List<Change> _changes = new List<Change>();
        private object _syncRoot = new object();
        private Timer _timer;
        private bool _processing = false;

        public Notifier(INotifier notifier)
        {
            _notifier = notifier;
        }

        ~Notifier()
        {
            Dispose(false);
        }

        public void Initialize()
        {
            _timer = new Timer(OnTimer, null, Settings.Default.NotificationCheckInterval,
                Settings.Default.NotificationCheckInterval);
        }


        public void Shutdown()
        {
            Dispose();

        }

        public void Dispose()
        {
            Dispose(true);
        }

        private void Dispose(bool isDisposing)
        {
            if(isDisposing)
            {
                if (_timer != null)
                    _timer.Dispose();
            }

            GC.SuppressFinalize(this);
        }

        public void AddChange(Change change)
        {
            lock(_syncRoot)
            {
                if(!_changes.Exists(c => c.ItemPath == change.ItemPath))
                {
                    _changes.Add(change);

                    Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.CHANGE_DETECTED,
                                                      "Change detected: {0}", change);
                }
                else
                {
                    Tracing.Default.Source.TraceEvent(TraceEventType.Verbose, (int)Tracing.Events.CHANGE_DETECTED,
                                                      "Change ignored, exists: {0}", change);
                }
            }
        }

        private void OnTimer(object state)
        {
            // Re-entrancy protection, only want one notification loop running at a time
            // and if it takes more than the timer period then it'll still be running
            if (!_processing)
            {
                _processing = true;

                try
                {
                    SendNotifications();
                }
                catch(Exception ex)
                {
                    Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.NOTIFICATION_ERROR,
                        "Unknown exception sending notification: {0}", ex.Message);

                    Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFICATION_ERROR, ex);
                }

                _processing = false;
            }
        }

        private void SendNotifications()
        {
            // Copy the list so we can work on it without the lock being owned too long in case
            // the notifications take a while
            List<Change> changesCopy = GetChangesCopy();
            
            DateTime now = DateTime.UtcNow;

            foreach(Change change in changesCopy)
            {
                if(change.NextNoficiationAttempt <= now)
                {
                    try
                    {
                        AttemptNotification(change);
                    }
                    catch(NotificationResultException ex)
                    {
                        Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.NOTIFICATION_ERROR,
                            "Error with notifying change: {0}, {1}", change.ChangeId, ex.Message);

                        HandleException(change, ex.Result.Retry);
                    }
                    catch(Exception ex)
                    {
                        Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.NOTIFICATION_ERROR,
                            "Error with notifying change: {0}, {1}", change.ChangeId, ex.Message);

                        Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFICATION_ERROR, ex);

                        HandleException(change, true);
                    }
                }
            }
        }

        private void HandleException(Change change, bool allowRetry)
        {
            if (!allowRetry || (change.Retries >= Settings.Default.MaxRetries))
            {
                FailChange(change, !allowRetry);
            }
            else
            {
                RetryChange(change);
            }
        }

        private List<Change> GetChangesCopy()
        {
            List<Change> changesCopy = new List<Change>();

            lock (_syncRoot)
            {
                foreach(Change change in _changes)
                {
                    changesCopy.Add(change);
                }
            }

            return changesCopy;
        }

        private void RetryChange(Change change)
        {
            change.NextNoficiationAttempt = DateTime.UtcNow + Settings.Default.RetryPeriod;
            change.Retries++;
            
            Tracing.Default.Source.TraceEvent(TraceEventType.Verbose, (int)Tracing.Events.WILL_RETRY,
                                              "Will retry change notification: {0} not before {1} will be attempt {2}/{3}",
                                              change.ChangeId, change.NextNoficiationAttempt, change.Retries, Settings.Default.MaxRetries);
        }

        private void AttemptNotification(Change change)
        {
            Tracing.Default.Source.TraceEvent(TraceEventType.Verbose, (int)Tracing.Events.NOTIFICATION_ATTEMPT,
                                              "Attempting notification: {0}", change.ChangeId);

            CheckCanNotify(change);

            Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.NOTIFY_SWISSCENTER,
                                              "Notifying SwissCenter of change: {0}", change.ChangeId);

            _notifier.SendEventNotification(change);

            RemoveChange(change);

            Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.NOTIFICATION_SENT,
                                              "Notification sent: {0}", change.ChangeId);
        }

        private static void CheckCanNotify(Change change)
        {
            if (change.ChangeType != WatcherChangeTypes.Deleted)
            {
                // Attempt to open the file for read access, only allow notification once we can do that.
                using(File.Open(change.ItemPath, FileMode.Open, FileAccess.Read, FileShare.Read))
                {
                }
            }
        }

        private void FailChange(Change change, bool forced)
        {
            if (!forced)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.MAX_RETRIES_REACHED,
                                                  "Max retries reached: {0}", change.ChangeId);
            }
            else
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.MAX_RETRIES_REACHED,
                                                  "No retry allowed: {0}", change.ChangeId);
            }

            RemoveChange(change);
        }

        private void RemoveChange(Change change)
        {
            lock(_syncRoot)
            {
                _changes.Remove(change);
            }
        }
    }
}
