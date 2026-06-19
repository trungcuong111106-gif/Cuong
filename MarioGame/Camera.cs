using Microsoft.Xna.Framework;

namespace MarioGame
{
    public class Camera
    {
        public Vector2 Position { get; set; }
        private const float Smoothing = 0.1f;

        public Matrix GetViewMatrix()
        {
            return Matrix.CreateTranslation(-Position.X + 200, -Position.Y + 250, 0);
        }

        public void Update(Vector2 playerPosition, int viewportWidth, int viewportHeight)
        {
            // Smooth camera follow
            Vector2 targetPosition = new Vector2(
                playerPosition.X - viewportWidth / 4,
                playerPosition.Y - viewportHeight / 3
            );

            Position = Vector2.Lerp(Position, targetPosition, Smoothing);

            // Giới hạn camera
            if (Position.X < 0) Position.X = 0;
            if (Position.Y < 0) Position.Y = 0;
        }
    }
}
