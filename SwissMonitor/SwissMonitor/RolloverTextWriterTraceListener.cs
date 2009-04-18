using System;
using System.Collections;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Security.Permissions;
using System.Text;

namespace Swiss.Monitor
{
    [HostProtection(SecurityAction.LinkDemand, Synchronization = true)]
    public class RolloverTextWriterTraceListener : TraceListener
    {
        private TextWriter _writer;
        private string _filename;
        private DateTime _fileOpened;
        private FileStream _fileStream;

        public TextWriter Writer
        {
            get
            {
                EnsureWriter();
                return _writer;
            }
            set { _writer = value; }
        }

        public RolloverTextWriterTraceListener()
        {
        }

        public RolloverTextWriterTraceListener(string filename)
        {
            _filename = filename;
        }

        public RolloverTextWriterTraceListener(string filename, string name) : base(name)
        {
            _filename = filename;
        }

        public override void Write(string message)
        {
            if(NeedIndent)
                WriteIndent();

            Writer.Write(message);
        }

        public override void WriteLine(string message)
        {
            if (NeedIndent)
                WriteIndent();

            Writer.WriteLine(message);

            NeedIndent = true;
        }

        public override void Flush()
        {
            Writer.Flush();
        }

        public override void Close()
        {
            if (_writer != null)
                _writer.Close();

            if (_fileStream != null)
                _fileStream.Close();

            _writer = null;
            _fileStream = null;
        }

        private void EnsureWriter()
        {
            if (IsRolloverRequired())
                DoRollover();

            if(_writer == null)
            {
                _fileStream = new FileStream(FilePath, FileMode.Append, FileAccess.Write, FileShare.Read);
                _writer = new StreamWriter(_fileStream, Encoding.UTF8, 0x1000);
                _fileOpened = DateTime.UtcNow;
            }
        }

        private string FilePath
        {
            get
            {
                return _filename;
            }
        }

        private string RolloverFilePath
        {
            get
            {
                return GetFilenameFromDate(_fileOpened);
            }
        }

        private bool IsRolloverRequired()
        {
            return (_fileOpened != default(DateTime)) && (DateTime.UtcNow.Date != _fileOpened.Date);
        }

        private void DoRollover()
        {
            if (File.Exists(FilePath) && !File.Exists(RolloverFilePath))
            {
                Close();
                File.Move(FilePath, RolloverFilePath);

                CleanupOldLogs();
            }
        }

        private void CleanupOldLogs()
        {
            string directoryName = Path.GetDirectoryName(FilePath);

            string searchPattern = Path.GetFileNameWithoutExtension(_filename) + "_*" + Path.GetExtension(FilePath);
            string[] files = Directory.GetFiles(directoryName, searchPattern, SearchOption.TopDirectoryOnly);

            if (files.Length > Settings.Default.KeepLastLogs)
            {
                Array.Sort(files, new ReverseComparer());

                for(int fileNum = Settings.Default.KeepLastLogs; fileNum < files.Length; fileNum++)
                {
                    try
                    {
                        // Try to delete the old log and ignore any exceptions as we don't really care
                        File.Delete(files[fileNum]);
                    }
                    catch
                    {
                    }
                }
            }
        }

        private string GetFilenameFromDate(DateTime date)
        {
            return string.Format("{0}{1}{2}_{3}{4}",
                                 Path.GetDirectoryName(_filename),
                                 Path.DirectorySeparatorChar,
                                 Path.GetFileNameWithoutExtension(_filename),
                                 date.ToString("yyyy-MM-dd"),
                                 Path.GetExtension(_filename));
        }

        protected override void Dispose(bool disposing)
        {
            base.Dispose(disposing);

            if(disposing)
            {
                Close();
            }

            GC.SuppressFinalize(this);
        }
    }


    public class ReverseComparer : IComparer<string>
    {
        private readonly CaseInsensitiveComparer _comparer = new CaseInsensitiveComparer();

        // Calls CaseInsensitiveComparer.Compare with the parameters reversed.
        public int Compare(string x, string y)
        {
            return _comparer.Compare(y, x);
        }
    }
}