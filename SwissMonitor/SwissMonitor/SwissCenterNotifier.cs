using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Net;
using System.Text;
using System.Web;
using System.Xml;

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
                                   {"IsDirectory", change.IsDirectory ? "Yes" : "No"}
                               };

            if(change is RenameChange)
                postData.Add("OldPath", ((RenameChange)change).OldPath);

            byte[] data = BuildPostData(postData);

            Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFY_SWISSCENTER, Encoding.UTF8.GetString(data));

            HttpWebRequest request = BuildPostRequest(Settings.Default.NotificationUri, data);

            using (Stream requestStream = request.GetRequestStream())
            {
                requestStream.Write(data, 0, data.Length);
                requestStream.Close();
            }

            WebResponse response = request.GetResponse();
            using(Stream responseStream = response.GetResponseStream())
            using(StreamReader reader = new StreamReader(responseStream))
            {
                string result = "";
                while (reader.Peek() >= 0)
                {
                    result += reader.ReadLine();
                }

                Tracing.Default.Source.TraceData(TraceEventType.Verbose, (int)Tracing.Events.NOTIFICATION_RESULTS,
                                                 change.ChangeId, result);

                NotificationResult notificationResult = ExtractResult(result);

                Tracing.Default.Source.TraceEvent(TraceEventType.Information, (int)Tracing.Events.NOTIFICATION_RESULTS,
                                                 "Results received: {0} {1}", change.ChangeId, notificationResult.ToString());

                ValidateResult(notificationResult);
            }

            response.Close();
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

        private static NotificationResult ExtractResult(string result)
        {
            NotificationResult notificationResult = new NotificationResult
                                                    {
                                                        Status = NotificationResult.NotificationStatus.Failed,
                                                        Retry = true,
                                                    };
            try
            {
                const string swissNamespace = "http://www.swisscenter.co.uk/schemas/2009/03/SwissMonitor";

                XmlDocument document = new XmlDocument();
                document.LoadXml(result);

                XmlElement root = document["result", swissNamespace];

                if(root == null)
                    throw new SwissCenterNotificationException("Invalid xml result", result);

                XmlElement statusElement = root["status", swissNamespace];

                if(statusElement == null)
                    throw new SwissCenterNotificationException("Missing status element in result XML", result);

                notificationResult.Status = (NotificationResult.NotificationStatus)
                                            Enum.Parse(typeof(NotificationResult.NotificationStatus),
                                                       statusElement.InnerText, true);


                XmlElement messageElement = root["message", swissNamespace];

                if (messageElement != null)
                    notificationResult.Message = messageElement.InnerText;

                XmlElement retryElement = root["retry", swissNamespace];

                if (retryElement != null)
                    notificationResult.Retry = bool.Parse(retryElement.InnerText);
            }
            catch(XmlException ex)
            {
                throw new SwissCenterNotificationException("Error loading XML results document", ex, result);
            }
            catch(ArgumentNullException ex)
            {
                throw new SwissCenterNotificationException("One or more required entries in the XML results is NULL", ex, result);
            }
            catch(ArgumentException ex)
            {
                throw new SwissCenterNotificationException("Unable to parse XML results document, invalid format", ex, result);
            }
            catch (FormatException ex)
            {
                throw new SwissCenterNotificationException("Unable to parse XML results document, invalid format", ex, result);
            }

            return notificationResult;
        }

        private static void ValidateResult(NotificationResult result)
        {
            if (result.Status != NotificationResult.NotificationStatus.Ok)
            {
                throw new NotificationResultException(
                    string.Format("SwissCenter returned a non-OK status code: {0}", result),
                    result);
            }
        }
    }
}