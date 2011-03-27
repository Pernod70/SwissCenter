using System;
using System.Collections;
using System.Collections.Generic;

namespace Swiss.Monitor.Configuration
{
    public class NullIgnoredExtensionsCollection : IEnumerable<IgnoredExtensionConfigurationElement>
    {
        /// <summary>
        ///                     Returns an enumerator that iterates through the collection.
        /// </summary>
        /// <returns>
        ///                     A <see cref="T:System.Collections.Generic.IEnumerator`1" /> that can be used to iterate through the collection.
        /// </returns>
        /// <filterpriority>1</filterpriority>
        public IEnumerator<IgnoredExtensionConfigurationElement> GetEnumerator()
        {
            yield break;
        }

        /// <summary>
        ///                     Returns an enumerator that iterates through a collection.
        /// </summary>
        /// <returns>
        ///                     An <see cref="T:System.Collections.IEnumerator" /> object that can be used to iterate through the collection.
        /// </returns>
        /// <filterpriority>2</filterpriority>
        IEnumerator IEnumerable.GetEnumerator()
        {
            return GetEnumerator();
        }
    }
}
