using System.Runtime.InteropServices;
using System.Text;

namespace Swiss.Monitor
{
    public class IniFile
    {
        private readonly IIniFileReader _iniFileReader;
        private readonly string _path;

        public IniFile(string path, IIniFileReader iniFilereader)
        {
            _path = path;
            _iniFileReader = iniFilereader;
        }

        public IniFileValue this[string section, string key]
        {
            get
            {
                return new IniFileValue(_iniFileReader.Lookup(_path, section, key));
            }
        }
    }
}