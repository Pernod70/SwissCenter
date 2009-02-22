using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Configuration;
using System.Diagnostics;

namespace Swiss.Monitor
{
    internal class ConfigManager : IConfigManager
    {
        private readonly IDictionary<string, object> _valueCache = new Dictionary<string, object>();
        private readonly object _syncLock = new object();

        public T GetConfigItem<T>(string key, T defaultValue)
        {
            if(IsInCache(key))
            {
                return GetFromCache<T>(key);
            }

            string val = ConfigurationManager.AppSettings[key];
            T result = defaultValue;

            if (val != null)
            {
                TypeConverter converter = TypeDescriptor.GetConverter(typeof(T));
                if(converter.CanConvertFrom(typeof(string)))
                {
                    try
                    {
                        result = (T)converter.ConvertFromInvariantString(val);
                    }
                    catch(FormatException)
                    {
                        Tracing.Default.Source.TraceEvent(TraceEventType.Warning, (int)Tracing.Events.CONFIGURATION_ERROR,
                            "Unable to convert configuration value to correct type: {0} = {1}", key, val);
                    }
                }
            }

            AddToCache(key, result);

            Tracing.Default.Source.TraceInformation("{0} = {1}", key, result);

            return result;
        }

        private T GetFromCache<T>(string key)
        {
            lock(_syncLock)
            {
                return (T)_valueCache[key];
            }
        }

        private bool IsInCache(string key)
        {
            lock(_syncLock)
            {
                return _valueCache.ContainsKey(key);
            }
        }

        private void AddToCache(string key, object val)
        {
            lock(_syncLock)
            {
                _valueCache[key] = val;
            }
        }
    }
}