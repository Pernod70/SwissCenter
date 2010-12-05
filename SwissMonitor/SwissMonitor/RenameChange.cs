namespace Swiss.Monitor
{
    public class RenameChange : Change
    {
        public string OldPath { get; set; }

        public override string ToString()
        {
            return string.Format("ID = {0}, ChangeType = {1}, Path = \"{2}\", OldPath = \"{3}\", Directory = {4}",
                ChangeId, ChangeType, ItemPath, OldPath, IsDirectory);
        }
    }
}