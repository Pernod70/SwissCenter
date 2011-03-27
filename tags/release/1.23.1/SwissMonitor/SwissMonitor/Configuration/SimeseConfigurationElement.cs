using System;
using System.Collections.Generic;
using System.Configuration;
using System.Text;

namespace Swiss.Monitor.Configuration
{
    public class SimeseConfigurationElement : ConfigurationElement
    {
        [ConfigurationProperty("iniPath", IsRequired = false)]
        public string IniPath
        {
            get { return (string)this["iniPath"]; }
            set { this["iniPath"] = value; }
        }

        [ConfigurationProperty("port", DefaultValue = 8080, IsRequired = false)]
        public int Port
        {
            get { return (int)this["port"]; }
            set { this["port"] = value; }
        }
    }
}
