using System;
using System.Diagnostics;
using System.IO;

namespace Swiss.Monitor
{
    class Settings
    {
        private static Settings _default = new Settings(new ConfigManager());
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
                
                return _configManager.GetConfigItem("SimesePort", 8080);
            }
        }

        private string _swissCenterConnectionString;

        public string SwissCenterConnectionString
        {
            get
            {
                if(_swissCenterConnectionString == null)
                {
                    _swissCenterConnectionString = _configManager.GetConfigItem("SwissCenterConnectionString",
                                                                                String.Format(
                                                                                    "server={0};user id={1}; password={2}; database={3}; pooling=false",
                                                                                    SwissCenterIni[null, "DB_HOST"],
                                                                                    SwissCenterIni[null, "DB_USERNAME"],
                                                                                    SwissCenterIni[null, "DB_PASSWORD"],
                                                                                    SwissCenterIni[null, "DB_DATABASE"]));
                }

                return _swissCenterConnectionString;
            }
        }

        private string _swissCenterIniPath;

        private string SwissCenterIniPath
        {
            get
            {
                if(_swissCenterIniPath == null)
                {
                    _swissCenterIniPath = _configManager.GetConfigItem("SwissCenterIniPath",
                                                                       Path.Combine(SimeseAppDataPath,
                                                                                    @"Data\Config\Swisscenter.ini"));
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
                    _simeseIniPath = _configManager.GetConfigItem("SimeseIniPath",
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
                    _notificationUri = _configManager.GetConfigItem("NotificationUri",
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
                return _configManager.GetConfigItem("NotificationCheckInterval", 30000);
            }
        }

        public int MaxRetries
        {
            get
            {
                return _configManager.GetConfigItem("MaxRetries", 2);
            }
        }

        public TimeSpan RetryPeriod
        {
            get
            {
                return _configManager.GetConfigItem("RetryPeriod", TimeSpan.FromMinutes(1));
            }
        }

        public int DatabaseConnectionRetryPeriod
        {
            get { return (int)_configManager.GetConfigItem("DatabaseConnectionRetryPeriod", TimeSpan.FromMinutes(5)).TotalMilliseconds; }
        }

        public string LocationProviderType
        {
            get
            {
                return _configManager.GetConfigItem("LocationProviderType", typeof(SwissMonitorLocations).AssemblyQualifiedName);
            }
        }

        private readonly IConfigManager _configManager;

        public Settings(IConfigManager configManager)
        {
            _configManager = configManager;
            SimeseIni = new IniFile(SimeseIniPath, new WindowsIniFileReader());
            SwissCenterIni = new IniFile(SwissCenterIniPath, new SwissIniFileReader());
        }
    }
}