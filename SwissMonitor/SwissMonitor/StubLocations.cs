using System.Collections.Generic;

namespace Swiss.Monitor
{
    internal class StubLocations : IMonitorLocations
    {
        public IEnumerable<string> GetLocations()
        {
            yield return @"c:\dump\monitorme";
        }
    }
}