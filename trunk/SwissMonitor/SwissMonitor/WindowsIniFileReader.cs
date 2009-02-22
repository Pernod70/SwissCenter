using System;
using System.Runtime.InteropServices;
using System.Text;

namespace Swiss.Monitor
{
    class WindowsIniFileReader : IIniFileReader
    {
        public string Lookup(string iniFilePath, string section, string key)
        {
            StringBuilder result = new StringBuilder(256);
            GetPrivateProfileString(section, key, null, result, (uint)result.Capacity, iniFilePath);

            return result.ToString();
        }

        [DllImport("Kernel32.dll", CallingConvention = CallingConvention.Winapi, CharSet = CharSet.Auto, ExactSpelling = false)]
        private static extern UInt32 GetPrivateProfileString(string section, string keyName, string defaultValue,
                                                             StringBuilder result, UInt32 size, string fileName);
    }
}
