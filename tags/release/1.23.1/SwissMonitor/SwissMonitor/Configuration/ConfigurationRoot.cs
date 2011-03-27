using System;
using System.Configuration;

namespace Swiss.Monitor.Configuration
{
    public class ConfigurationRoot : ConfigurationSection
    {
        [ConfigurationProperty("debug", IsRequired = false)]
        public DebugConfigurationElement Debug
        {
            get { return (DebugConfigurationElement)this["debug"]; }
            set { this["debug"] = value; }
        }

        [ConfigurationProperty("simese", IsRequired = false)]
        public SimeseConfigurationElement Simese
        {
            get { return (SimeseConfigurationElement)this["simese"]; }
            set { this["simese"] = value; }
        }

        [ConfigurationProperty("swissCenter", IsRequired = false)]
        public SwissCenterConfigurationElement SwissCenter
        {
            get { return (SwissCenterConfigurationElement)this["swissCenter"]; }
            set { this["swissCenter"] = value; }
        }

        [ConfigurationProperty("ignoredExtensions")]
        public IgnoredExtensionCollection IgnoredExtensions
        {
            get { return (IgnoredExtensionCollection)this["ignoredExtensions"]; }
            set { this["ignoredExtensions"] = value; }
        }


        [ConfigurationProperty("keepLastLogs", IsRequired = false, DefaultValue = 3)]
        public int KeepLastLogs
        {
            get { return (int)this["keepLastLogs"]; }
            set { this["keepLastLogs"] = value; }
        }
       

        [ConfigurationProperty("notificationCheckInterval", IsRequired = false, DefaultValue = 30000)]
        public int NotificationCheckInterval
        {
            get { return (int)this["notificationCheckInterval"]; }
            set { this["notificationCheckInterval"] = value; }
        }

        [ConfigurationProperty("maxRetries", IsRequired = false, DefaultValue = 2)]
        public int MaxRetries
        {
            get { return (int)this["maxRetries"]; }
            set { this["maxRetries"] = value; }
        }

        [ConfigurationProperty("retryPeriod", IsRequired = false)]
        public TimeSpan RetryPeriod
        {
            get { return (TimeSpan)this["retryPeriod"] == default(TimeSpan) ? TimeSpan.FromMinutes(1) : (TimeSpan)this["retryPeriod"]; }
            set { this["retryPeriod"] = value; }
        }

        
    }
}