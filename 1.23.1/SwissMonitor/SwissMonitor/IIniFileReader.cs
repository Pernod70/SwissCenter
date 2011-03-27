using System;

namespace Swiss.Monitor
{
    public interface IIniFileReader
    {
        string Lookup(string iniFilePath, string section, string key);
    }
}
