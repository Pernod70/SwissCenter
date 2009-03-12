using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Net;
using System.Text;
using System.Web;

namespace Swiss.Monitor
{
    public class SwissCenterNotifier : INotifier
    {
        public void SendEventNotification(Change change)
        {
            Dictionary<string, object> postData = new Dictionary<string, object>
                               {
                                   {"Path", change.ItemPath},
                                   {"Type", change.ChangeType},
                                   {"ChangedDate", DateTime.UtcNow.ToString("u")},
                               };

            if(change is RenameChange)
                postData.Add("OldPath", ((RenameChange)change).OldPath);

            byte[] data = BuildPostData(postData);

            Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFY_SWISSCENTER, Encoding.UTF8.GetString(data));

            try
            {
                HttpWebRequest request = BuildPostRequest(Settings.Default.NotificationUri, data);

                Stream requestStream = request.GetRequestStream();
                requestStream.Write(data, 0, data.Length);
                requestStream.Close();

                WebResponse response = request.GetResponse();
                using(Stream responseStream = response.GetResponseStream())
                using(StreamReader reader = new StreamReader(responseStream))
                {
                    string result = reader.ReadToEnd();
                   
                    Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFICATION_RESULTS,
                        change.ChangeId, result);
                }

                response.Close();
            }
            catch(WebException ex)
            {
                Tracing.Default.Source.TraceEvent(TraceEventType.Error, (int)Tracing.Events.NOTIFICATION_ERROR, "Unable to notify SwissCenter: {0}", ex.Message);
                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFICATION_ERROR, ex);

                throw;
            }
        }

        private static HttpWebRequest BuildPostRequest(string uri, byte[] postData)
        {
            HttpWebRequest request = (HttpWebRequest)WebRequest.Create(uri);
            request.Method = "POST";
            request.ContentType = "application/x-www-form-urlencoded";
            request.ContentLength = postData.Length;
            return request;
        }

        private static byte[] BuildPostData(IDictionary<string, object> postData)
        {
            StringBuilder builder = new StringBuilder();

            foreach(KeyValuePair<string, object> pair in postData)
            {
                builder.AppendFormat("{0}={1}&",
                    HttpUtility.UrlEncode(pair.Key),
                    HttpUtility.UrlEncode(pair.Value.ToString()));
            }

            return Encoding.UTF8.GetBytes(builder.ToString());
        }
    }
}