using System.Collections.Generic;

namespace Swiss.Monitor
{
    internal interface IMonitorLocations
    {
        IEnumerable<string> GetLocations();
    }
}