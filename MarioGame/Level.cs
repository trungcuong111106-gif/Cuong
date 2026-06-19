using Microsoft.Xna.Framework;
using Microsoft.Xna.Framework.Content;
using Microsoft.Xna.Framework.Graphics;
using System.Collections.Generic;

namespace MarioGame
{
    public class Level
    {
        private List<Platform> platforms;
        private Texture2D platformTexture;

        public Level()
        {
            platforms = new List<Platform>();
            CreateLevel();
        }

        private void CreateLevel()
        {
            // Nền đất chính
            platforms.Add(new Platform(new Rectangle(0, 650, 3000, 50), Color.SaddleBrown));

            // Các platform
            platforms.Add(new Platform(new Rectangle(300, 550, 150, 20), Color.Brown));
            platforms.Add(new Platform(new Rectangle(550, 500, 150, 20), Color.Brown));
            platforms.Add(new Platform(new Rectangle(800, 450, 150, 20), Color.Brown));
            platforms.Add(new Platform(new Rectangle(1050, 400, 150, 20), Color.Brown));
            platforms.Add(new Platform(new Rectangle(1300, 350, 150, 20), Color.Brown));
            
            // Các platform khác
            platforms.Add(new Platform(new Rectangle(1600, 400, 150, 20), Color.Brown));
            platforms.Add(new Platform(new Rectangle(1900, 450, 150, 20), Color.Brown));
            platforms.Add(new Platform(new Rectangle(2200, 500, 150, 20), Color.Brown));
            
            // Platform nhỏ
            platforms.Add(new Platform(new Rectangle(400, 450, 80, 20), Color.DarkGoldenrod));
            platforms.Add(new Platform(new Rectangle(650, 400, 80, 20), Color.DarkGoldenrod));
            platforms.Add(new Platform(new Rectangle(900, 350, 80, 20), Color.DarkGoldenrod));
        }

        public void LoadContent(ContentManager content, GraphicsDevice graphicsDevice)
        {
            // Tạo texture đơn giản
            platformTexture = new Texture2D(graphicsDevice, 1, 1);
            Color[] data = { Color.White };
            platformTexture.SetData(data);
        }

        public bool CheckCollision(Rectangle boundingBox, out Vector2 pushback)
        {
            pushback = Vector2.Zero;

            foreach (Platform platform in platforms)
            {
                if (boundingBox.Intersects(platform.BoundingBox))
                {
                    // Tính toán pushback
                    Rectangle intersection = Rectangle.Intersect(boundingBox, platform.BoundingBox);
                    
                    // Nếu người chơi đang rơi (di chuyển xuống)
                    if (intersection.Height < intersection.Width)
                    {
                        // Đẩy lên
                        pushback.Y = -intersection.Height;
                    }
                    else if (boundingBox.Center.X < platform.BoundingBox.Center.X)
                    {
                        // Đẩy sang trái
                        pushback.X = -intersection.Width;
                    }
                    else
                    {
                        // Đẩy sang phải
                        pushback.X = intersection.Width;
                    }

                    return true;
                }
            }

            return false;
        }

        public void Draw(SpriteBatch spriteBatch)
        {
            foreach (Platform platform in platforms)
            {
                spriteBatch.Draw(platformTexture, platform.BoundingBox, platform.Color);
            }
        }
    }

    public class Platform
    {
        public Rectangle BoundingBox { get; set; }
        public Color Color { get; set; }

        public Platform(Rectangle bounds, Color color)
        {
            BoundingBox = bounds;
            Color = color;
        }
    }
}
