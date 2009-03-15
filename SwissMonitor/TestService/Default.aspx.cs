using System;
using System.Configuration;
using System.IO;
using System.Text;
using System.Xml;

namespace TestService
{
    public partial class _Default : System.Web.UI.Page
    {
        protected void Page_Load(object sender, EventArgs e)
        {
            const string swissNamespace = "http://www.swisscenter.co.uk/schemas/2009/03/SwissMonitor";

            
            //StringBuilder output = new StringBuilder();
            XmlWriterSettings writerSettings = new XmlWriterSettings {Encoding = Encoding.UTF8, Indent = true};

            
            using (MemoryStream output = new MemoryStream())
            using (XmlWriter result = XmlWriter.Create(output, writerSettings))
            {
                result.WriteStartDocument();
                result.WriteStartElement("result", swissNamespace);
                result.WriteStartElement("status", swissNamespace);
                result.WriteValue(ConfigurationManager.AppSettings["Status"]);
                result.WriteEndElement();
                result.WriteStartElement("message", swissNamespace);
                result.WriteValue(GetData());
                result.WriteEndElement();
                result.WriteStartElement("retry", swissNamespace);
                result.WriteValue(ConfigurationManager.AppSettings["retry"]);
                result.WriteEndElement();
                result.WriteEndElement();

                result.Flush();

                Response.Write(Encoding.UTF8.GetString(output.GetBuffer()));
            }
        }

        private string GetData()
        {
            string data = String.Empty;
            foreach(string key in Request.Form.AllKeys)
            {
                if (!string.IsNullOrEmpty(key))
                {
                    data += string.Format(string.Format("{0} = {1}, ", key, Request.Form[key]));
                }
            }

            return data;
        }
    }
}
