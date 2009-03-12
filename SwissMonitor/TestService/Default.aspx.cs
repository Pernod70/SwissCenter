using System;

namespace TestService
{
    public partial class _Default : System.Web.UI.Page
    {
        protected void Page_Load(object sender, EventArgs e)
        {
            foreach(string key in Request.Form.AllKeys)
            {
                if(!string.IsNullOrEmpty(key))
                    Response.Write(string.Format("{0} = {1}, ", key, Request.Form[key]));
            }
        }
    }
}
