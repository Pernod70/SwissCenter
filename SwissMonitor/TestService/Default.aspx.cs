using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace TestService
{
    public partial class _Default : System.Web.UI.Page
    {
        protected void Page_Load(object sender, EventArgs e)
        {
            Response.Write("Path = " + Request.Form["Path"]);
            Response.Write(", Type = " + Request.Form["Type"]);
            Response.Write(", Date = " + Request.Form["ChangedDate"]);
        }
    }
}
