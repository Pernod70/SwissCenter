using System;
using System.Configuration;

namespace Swiss.Monitor.Configuration
{
    public class SwissCenterConfigurationElement : ConfigurationElement
    {
        [ConfigurationProperty("database")]
        public string Database
        {
            get { return (string)this["database"]; }
            set { this["database"] = value; }
        }

        [ConfigurationProperty("iniPath", IsRequired = false)]
        public string IniPath
        {
            get { return (string)this["iniPath"]; }
            set { this["iniPath"] = value; }
        }
    }
}
