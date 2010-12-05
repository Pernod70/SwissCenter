using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using Swiss.Monitor.Configuration;

namespace Swiss.Monitor
{
    class FileMonitor : IDisposable
    {
        private readonly List<FileSystemWatcher> _watchersFile = new List<FileSystemWatcher>();
        private readonly List<FileSystemWatcher> _watchersDirectory = new List<FileSystemWatcher>();
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
                FileSystemWatcher watcherFile = new FileSystemWatcher(monitorPath);
                watcherFile.Changed += OnFileEvent;
                watcherFile.Created += OnFileEvent;
                watcherFile.Deleted += OnFileEvent;
                watcherFile.Renamed += OnFileRenamedEvent;

                watcherFile.IncludeSubdirectories = true;
                watcherFile.NotifyFilter = NotifyFilters.LastWrite | NotifyFilters.CreationTime | NotifyFilters.FileName;

                _watchersFile.Add(watcherFile);

                watcherFile.EnableRaisingEvents = true;

                FileSystemWatcher watcherDirectory = new FileSystemWatcher(monitorPath);
                watcherDirectory.Created += OnDirectoryEvent;
                watcherDirectory.Deleted += OnDirectoryEvent;
                watcherDirectory.Renamed += OnDirectoryRenamedEvent;

                watcherDirectory.IncludeSubdirectories = true;
                watcherDirectory.NotifyFilter = NotifyFilters.LastWrite | NotifyFilters.CreationTime | NotifyFilters.DirectoryName;

                _watchersDirectory.Add(watcherDirectory);

                watcherDirectory.EnableRaisingEvents = true;

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
                foreach (FileSystemWatcher watcher in _watchersFile)
                {
                    KillWatcherFile(watcher);
                }

                foreach (FileSystemWatcher watcher in _watchersDirectory)
                {
                    KillWatcherDirectory(watcher);
                }

                _watchersFile.Clear();
                _watchersDirectory.Clear();

                if(_notifier != null)
                    _notifier.Dispose();
            }

            GC.SuppressFinalize(this);
        }

        private void KillWatcherFile(FileSystemWatcher watcher)
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

        private void KillWatcherDirectory(FileSystemWatcher watcher)
        {
            if (watcher != null)
            {
                watcher.Created -= OnDirectoryEvent;
                watcher.Deleted -= OnDirectoryEvent;
                watcher.Renamed -= OnDirectoryRenamedEvent;
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
                    IsDirectory = false,
                    ItemPath = e.FullPath,
                    ChangeType = e.ChangeType
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
                    IsDirectory = false,
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

        private void OnDirectoryEvent(object sender, FileSystemEventArgs e)
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
                    IsDirectory = true,
                    ItemPath = e.FullPath,
                    ChangeType = e.ChangeType
                };

                _notifier.AddChange(change);
            }
            catch (Exception ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.CANNOT_PROCESS_CHANGE,
                                                  "Unable to process change: {0}", ex.Message);

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.CANNOT_PROCESS_CHANGE,
                                                 ex);
            }
        }

        private void OnDirectoryRenamedEvent(object sender, RenamedEventArgs e)
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
                    IsDirectory = true,
                    ItemPath = e.FullPath,
                    ChangeType = e.ChangeType,
                    OldPath = e.OldFullPath
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
