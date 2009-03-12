using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using Swiss.Monitor.Configuration;

namespace Swiss.Monitor
{
    class FileMonitor : IDisposable
    {
        private readonly List<FileSystemWatcher> _watchers = new List<FileSystemWatcher>();
        private readonly Notifier _notifier;

        public FileMonitor(INotifier notifier)
        {
            _notifier = new Notifier(notifier);
        }

        public void Initialize()
        {
            Tracing.Default.Source.TraceEvent(TraceEventType.Verbose, (int)Tracing.Events.INITIALISING_MONITOR, "Initialising monitor");

            _notifier.Initialize();
        }

        ~FileMonitor()
        {
            Dispose(false);
        }

        public void AddMonitor(string monitorPath)
        {
            if(!Directory.Exists(monitorPath))
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.MONITOR_ERROR,
                                                  "Directory does not exist: {0}", monitorPath);
                return;
            }

            try
            {
                FileSystemWatcher watcher = new FileSystemWatcher(monitorPath);
                watcher.Changed += OnFileEvent;
                watcher.Created += OnFileEvent;
                watcher.Deleted += OnFileEvent;
                watcher.Renamed += OnFileRenamedEvent;

                watcher.IncludeSubdirectories = true;
                watcher.NotifyFilter = NotifyFilters.LastWrite | NotifyFilters.CreationTime | NotifyFilters.FileName |
                                       NotifyFilters.DirectoryName;

                _watchers.Add(watcher);

                watcher.EnableRaisingEvents = true;

            }
            catch(ArgumentException)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.MONITOR_ERROR,
                                                  "Unable to create monitor: {0}", monitorPath);
            }
            catch(AccessViolationException)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.MONITOR_ERROR,
                                     "Access violation creating monitor: {0}", monitorPath);
            }
        }


        public void Shutdown()
        {
            Dispose();
        }

        public void Dispose()
        {
            Dispose(true);
        }

        void Dispose(bool isDisposing)
        {
            if(isDisposing)
            {
                foreach (FileSystemWatcher watcher in _watchers)
                {
                    KillWatcher(watcher);
                }

                _watchers.Clear();

                if(_notifier != null)
                    _notifier.Dispose();
            }

            GC.SuppressFinalize(this);
        }

        private void KillWatcher(FileSystemWatcher watcher)
        {
            if (watcher != null)
            {
                watcher.Changed -= OnFileEvent;
                watcher.Created -= OnFileEvent;
                watcher.Deleted -= OnFileEvent;
                watcher.Renamed -= OnFileRenamedEvent;
                watcher.Dispose();
            }
        }

        private void OnFileEvent(object sender, FileSystemEventArgs e)
        {
            if (IsIgnored(e.FullPath))
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Verbose, (int)Tracing.Events.IGNORING_EXTENSION,
                    "Ignoring change due to extension: {0}", e.FullPath);

                return;
            }
            try
            {
                Change change = new Change
                                {
                                    ItemPath = e.FullPath,
                                    ChangeType = e.ChangeType,
                                };

                _notifier.AddChange(change);
            }
            catch(Exception ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.CANNOT_PROCESS_CHANGE,
                                                  "Unable to process change: {0}", ex.Message);

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_PROCESS_CHANGE,
                                                 ex);
            }
        }

        private void OnFileRenamedEvent(object sender, RenamedEventArgs e)
        {
            if (IsIgnored(e.FullPath))
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Verbose, (int)Tracing.Events.IGNORING_EXTENSION,
                    "Ignoring change due to extension: {0}", e.FullPath);

                return;
            }

            try
            {
                RenameChange change = new RenameChange
                {
                    ItemPath = e.FullPath,
                    ChangeType = e.ChangeType,
                    OldPath = e.OldFullPath,
                };

                _notifier.AddChange(change);
            }
            catch (Exception ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.CANNOT_PROCESS_CHANGE,
                                                  "Unable to process change: {0}", ex.Message);

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_PROCESS_CHANGE, ex);
            }
        }

        private static bool IsIgnored(string name)
        {
            string extension = Path.GetExtension(name);

            foreach(IgnoredExtensionConfigurationElement element in Settings.Default.IgnoredExtensions)
            {
                if (string.Compare(extension, element.Extension, true) == 0)
                    return true;
            }

            return false;
        }
    }
}
