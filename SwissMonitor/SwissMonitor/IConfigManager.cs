namespace Swiss.Monitor
{
    internal interface IConfigManager
    {
        T GetConfigItem<T>(string key, T defaultValue);
    }
}