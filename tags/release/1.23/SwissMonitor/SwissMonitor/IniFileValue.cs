using System;

namespace Swiss.Monitor
{
    public class IniFileValue
    {
        public string Value { get; set; }
        public bool IsNull
        {
            get { return string.IsNullOrEmpty(Value); }
        }

        public static explicit operator string(IniFileValue val)
        {
            return val.Value;
        }

        public static explicit operator int(IniFileValue val)
        {
            return Convert.ToInt32(val.Value);
        }

        public static explicit operator double(IniFileValue val)
        {
            return Convert.ToDouble(val.Value);
        }

        public static explicit operator bool(IniFileValue val)
        {
            return Convert.ToBoolean(val.Value);
        }

        public IniFileValue(string val)
        {
            Value = val;
        }

        public override string ToString()
        {
            return Value;
        }
    }
}