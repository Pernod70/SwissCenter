using System;
using System.IO;

namespace Swiss.Monitor
{
    class SwissIniFileReader : IIniFileReader
    {
        public string Lookup(string iniFilePath, string section, string key)
        {
            using(FileStream stream = File.Open(iniFilePath, FileMode.Open, FileAccess.Read))
            using(StreamReader reader = new StreamReader(stream))
            {
                string line = reader.ReadLine();

                while (line != null)
                {
                    string currentKey;
                    string currentValue;

                    if (ParseLine(line, out currentKey, out currentValue))
                    {
                        if (string.Compare(key, currentKey, false) == 0)
                            return currentValue;
                    }

                    line = reader.ReadLine();
                }
            }

            return null;
        }

        private bool ParseLine(string line, out string key, out string val)
        {
            key = null;
            val = null;

            line = line.Trim();

            if (line.StartsWith(";"))
                return false;

            int equals = line.IndexOf('=');

            if ((equals <= 0) || line.EndsWith("="))
                return false;

            key = line.Substring(0, equals);
            val = line.Substring(equals + 1);

            return true;
        }
    }
}
