using System;
using System.Configuration;

namespace Swiss.Monitor.Configuration
{
    public class IgnoredExtensionConfigurationElement : ConfigurationElement
    {
        [ConfigurationProperty("extension", IsRequired = true)]
        public string Extension
        {
            get { return (string)this["extension"]; }
            set { this["extension"] = value; }
        }
    }
}
