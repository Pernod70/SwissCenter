using System;
using System.Diagnostics;

namespace Swiss.Monitor
{
    internal static class LocationFactory
    {
        public static IMonitorLocations CreateLocations()
        {
            Type locationProviderType = Type.GetType(Settings.Default.LocationProviderType);

            if (locationProviderType == null)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Critical, (int)Tracing.Events.INVALID_LOCATION_PROVIDER,
                                                  "The specified location provider cannot be found or does not implement the IMonitorLocations interface.");

                return null;
            }

            return Activator.CreateInstance(locationProviderType) as IMonitorLocations;
        }
    }
}