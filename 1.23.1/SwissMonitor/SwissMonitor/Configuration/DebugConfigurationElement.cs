using System;
using System.Configuration;

namespace Swiss.Monitor.Configuration
{
    public class DebugConfigurationElement : ConfigurationElement
    {
        [ConfigurationProperty("notificationUri", IsRequired = false)]
        public string NotificationUri
        {
            get { return (string)this["notificationUri"]; }
            set { this["notificationUri"] = value; }
        }

        [ConfigurationProperty("locationProviderType", IsRequired = false, DefaultValue = "Swiss.Monitor.SwissMonitorLocations, SwissMonitor")]
        public string LocationProviderType
        {
            get { return (string)this["locationProviderType"]; }
            set { this["locationProviderType"] = value; }
        }

        [ConfigurationProperty("databaseConnectionRetryPeriod", IsRequired = false)]
        public TimeSpan DatabaseConnectionRetryPeriod
        {
            get
            {
                return (TimeSpan)this["databaseConnectionRetryPeriod"] == default(TimeSpan)
                           ? TimeSpan.FromMinutes(5)
                           : (TimeSpan)this["databaseConnectionRetryPeriod"];
            }

            set { this["databaseConnectionRetryPeriod"] = value; }
        }
    }
}
