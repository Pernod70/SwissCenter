using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading;
using System.Windows.Forms;

namespace ServiceKiller
{
    public partial class Form1 : Form
    {
        public Form1()
        {
            InitializeComponent();
        }

        private void killService_Click(object sender, EventArgs e)
        {
            EventWaitHandle waitHandle = new EventWaitHandle(false, EventResetMode.AutoReset, "SwissMonitor.WaitHandle");
            waitHandle.Set();
        }
    }
}
