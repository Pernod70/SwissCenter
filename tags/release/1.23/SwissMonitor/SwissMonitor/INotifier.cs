using System.IO;

namespace Swiss.Monitor
{
    public interface INotifier
    {
        void SendEventNotification(Change change);
    }
}