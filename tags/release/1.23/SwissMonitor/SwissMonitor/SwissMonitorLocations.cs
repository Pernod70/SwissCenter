using System;
using System.Collections.Generic;
using System.Data;

namespace Swiss.Monitor
{
    internal class SwissMonitorLocations : IMonitorLocations
    {
        public IEnumerable<string> GetLocations()
        {
            string connectionString = Settings.Default.SwissCenterConnectionString;
            IDbConnection connection = new MySql.Data.MySqlClient.MySqlConnection(connectionString);
            IDbCommand cmd = connection.CreateCommand();

            cmd.CommandType = CommandType.Text;
            cmd.CommandText = "select location_id, name from media_locations";

            using (connection)
            {
                connection.Open();

                IDataReader reader = cmd.ExecuteReader();

                if (reader != null)
                {
                    using (reader)
                    {
                        while (reader.Read())
                        {
                            yield return EnsureSlashes((string)reader["name"]);
                        }
                    }
                }
            }
        }

        private static string EnsureSlashes(string path)
        {
            return path.Replace('/', '\\');
        }
    }
}
