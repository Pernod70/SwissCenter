using System;
using System.Collections;
using System.Collections.Generic;
using System.Configuration;
using System.Text;

namespace Swiss.Monitor.Configuration
{
    public class IgnoredExtensionCollection : ConfigurationElementCollection, IEnumerable<IgnoredExtensionConfigurationElement>
    {
        protected override ConfigurationElement CreateNewElement()
        {
            return new IgnoredExtensionConfigurationElement();
        }

        protected override object GetElementKey(ConfigurationElement element)
        {
            return ((IgnoredExtensionConfigurationElement)element).Extension;
        }

        public new IgnoredExtensionConfigurationElement this[string extension]
        {
            get
            {
                return (IgnoredExtensionConfigurationElement)BaseGet(extension);
            }
        }

        public new IEnumerator<IgnoredExtensionConfigurationElement> GetEnumerator()
        {
            IEnumerator enumerator = base.GetEnumerator();

            while (enumerator.MoveNext())
                yield return (IgnoredExtensionConfigurationElement)enumerator.Current;
        }

        public override string ToString()
        {
            StringBuilder sb = new StringBuilder();
            foreach(IgnoredExtensionConfigurationElement element in this)
            {
                sb.AppendFormat("{0}, ", element.Extension);
            }

            return sb.ToString();
        }
    }
}
