using System;

namespace MarioGame
{
#if WINDOWS || LINUX
    public static class Program
    {
        [STAThread]
        static void Main()
        {
            using (var game = new MarioGame())
                game.Run();
        }
    }
#endif
}
