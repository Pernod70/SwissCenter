using System;
using System.Collections.Generic;
using System.Configuration;
using System.Diagnostics;
using System.IO;
using System.Reflection;
using Swiss.Monitor.Configuration;

namespace Swiss.Monitor
{
    class Settings
    {
        private static readonly Settings _default = new Settings();

        public static Settings Default { get { return _default; } }

        public IniFile SimeseIni { get; private set; }

        public IniFile SwissCenterIni { get; private set; }

        public int SimesePort
        {
            get
            {
                IniFileValue iniFileValue = SimeseIni["common", "port"];
                if(!iniFileValue.IsNull)
                    return (int)iniFileValue;

                return Root.Simese.Port;
            }
        }

        private string _swissCenterConnectionString;

        public string SwissCenterConnectionString
        {
            get
            {
                if(_swissCenterConnectionString == null)
                {
                    if(string.IsNullOrEmpty(Root.SwissCenter.Database))
                    {
                        _swissCenterConnectionString = String.Format(
                            "server={0};user id={1}; password={2}; database={3}; pooling=false",
                            SwissCenterIni[null, "DB_HOST"],
                            SwissCenterIni[null, "DB_USERNAME"],
                            SwissCenterIni[null, "DB_PASSWORD"],
                            SwissCenterIni[null, "DB_DATABASE"]);
                    }
                    else
                    {
                        _swissCenterConnectionString = Root.SwissCenter.Database;
                    }
                }

                return _swissCenterConnectionString;
            }
        }

        private string _swissCenterIniPath;

        private string SwissCenterIniPath
        {
            get
            {
                if (_swissCenterIniPath == null)
                {
                    _swissCenterIniPath = GetWithDefault(Root.SwissCenter.IniPath,
                                                         Path.Combine(SimeseAppDataPath, @"Data\Config\Swisscenter.ini"));
                }

                return _swissCenterIniPath;
            }
        }

        private string _simeseIniPath;

        private string SimeseIniPath
        {
            get
            {
                if (_simeseIniPath == null)
                {
                    _simeseIniPath = GetWithDefault(Root.Simese.IniPath,
                                                    Path.Combine(SimeseAppDataPath, "simese.ini"));
                }

                return _simeseIniPath;
            }
        }

        private string _notificationUri;

        public string NotificationUri
        {
            get
            {
                if (_notificationUri == null)
                {
                    _notificationUri = GetWithDefault(Root.Debug.NotificationUri,
                                                      "http://localhost:" + SimesePort + "/media_monitor.php");
                }

                return _notificationUri;
            }
        }


        private readonly string _simeseAppDataPath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData), "Simese");

        public string SimeseAppDataPath
        {
            get { return _simeseAppDataPath; }
        }

        public long NotificationCheckInterval
        {
            get
            {
                return Root.NotificationCheckInterval;
            }
        }

        public int MaxRetries
        {
            get
            {
                return Root.MaxRetries;
            }
        }

        public TimeSpan RetryPeriod
        {
            get
            {
                return Root.RetryPeriod;
            }
        }

        public TimeSpan DatabaseConnectionRetryPeriod
        {
            get { return Root.Debug.DatabaseConnectionRetryPeriod; }
        }

        public string LocationProviderType
        {
            get
            {
                return Root.Debug.LocationProviderType;
            }
        }

        public IEnumerable<IgnoredExtensionConfigurationElement> IgnoredExtensions
        {
            get { return Root.IgnoredExtensions; }
        }

        private ConfigurationRoot Root { get; set; }

        public Settings()
        {
            Root = (ConfigurationRoot)ConfigurationManager.GetSection("swissMonitor");

            if(Root == null)
                Root = new ConfigurationRoot();
            
            SimeseIni = new IniFile(SimeseIniPath, new WindowsIniFileReader());
            SwissCenterIni = new IniFile(SwissCenterIniPath, new SwissIniFileReader());

            DumpToLog();
        }

        private static string GetWithDefault(String inputString, string defaultString)
        {
            if (string.IsNullOrEmpty(inputString))
                return defaultString;

            return inputString;
        }

        public void DumpToLog()
        {
            PropertyInfo[] properties = GetType().GetProperties(BindingFlags.GetProperty | BindingFlags.Instance | BindingFlags.Public);

            foreach(PropertyInfo propertyInfo in properties)
            {
                try
                {
                    Tracing.Default.Source.TraceInformation("Setting: {0} = {1}",
                        propertyInfo.Name, propertyInfo.GetValue(this, null));
                }
                catch(Exception ex)
                {
                    Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.UNABLE_TO_READ_SETTING,
                        "Unable to read setting: {0}, {1}", propertyInfo.Name, ex.Message);
                }
            }
        }
    }
}