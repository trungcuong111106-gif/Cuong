using Microsoft.Xna.Framework;
using Microsoft.Xna.Framework.Content;
using Microsoft.Xna.Framework.Graphics;
using Microsoft.Xna.Framework.Input;

namespace MarioGame
{
    public class Player
    {
        public Vector2 Position { get; set; }
        public Vector2 Velocity { get; set; }
        private Texture2D texture;
        private const float MoveSpeed = 200f;
        private const float JumpForce = -350f;
        private const float Gravity = 600f;
        private const float MaxFallSpeed = 400f;
        private bool isJumping;
        private int width = 32;
        private int height = 48;

        public Rectangle BoundingBox => 
            new Rectangle((int)Position.X, (int)Position.Y, width, height);

        public Player(Vector2 startPosition)
        {
            Position = startPosition;
            Velocity = Vector2.Zero;
            isJumping = false;
        }

        public void LoadContent(ContentManager content, GraphicsDevice graphicsDevice)
        {
            // Tạo texture đơn giản cho Mario (màu đỏ)
            texture = new Texture2D(graphicsDevice, width, height);
            Color[] data = new Color[width * height];
            
            // Tạo hình dạng Mario đơn giản
            for (int i = 0; i < data.Length; i++)
            {
                int x = i % width;
                int y = i / width;
                
                // Đầu (vàng)
                if (x >= 8 && x < 24 && y >= 0 && y < 16)
                    data[i] = Color.Yellow;
                // Thân (đỏ)
                else if (x >= 8 && x < 24 && y >= 16 && y < 32)
                    data[i] = Color.Red;
                // Chân (nâu)
                else if ((x >= 10 && x < 15 || x >= 17 && x < 22) && y >= 32 && y < 48)
                    data[i] = new Color(139, 69, 19);
                else
                    data[i] = Color.Transparent;
            }
            
            texture.SetData(data);
        }

        public void Update(GameTime gameTime, Level level)
        {
            KeyboardState keyState = Keyboard.GetState();
            float deltaTime = (float)gameTime.ElapsedGameTime.TotalSeconds;

            // Xử lý di chuyển ngang
            Velocity.X = 0;
            if (keyState.IsKeyDown(Keys.Left) || keyState.IsKeyDown(Keys.A))
                Velocity.X = -MoveSpeed;
            if (keyState.IsKeyDown(Keys.Right) || keyState.IsKeyDown(Keys.D))
                Velocity.X = MoveSpeed;

            // Xử lý nhảy
            if ((keyState.IsKeyDown(Keys.Space) || keyState.IsKeyDown(Keys.Up) || keyState.IsKeyDown(Keys.W)) && !isJumping)
            {
                Velocity.Y = JumpForce;
                isJumping = true;
            }

            // Áp dụng trọng lực
            Velocity.Y += Gravity * deltaTime;
            if (Velocity.Y > MaxFallSpeed)
                Velocity.Y = MaxFallSpeed;

            // Cập nhật vị trí
            Position += Velocity * deltaTime;

            // Kiểm tra va chạm với platform
            if (level.CheckCollision(BoundingBox, out Vector2 pushback))
            {
                Position += pushback;
                
                // Nếu chúng ta đang rơi xuống và va chạm phía trên platform
                if (Velocity.Y > 0)
                {
                    Velocity.Y = 0;
                    isJumping = false;
                }
            }

            // Giới hạn vị trí ngang
            if (Position.X < 0) Position.X = 0;
            if (Position.X > 3000) Position.X = 3000;
            
            // Nếu rơi xuống quá thấp, reset vị trí
            if (Position.Y > 800) 
                Position = new Vector2(100, 400);
        }

        public void Draw(SpriteBatch spriteBatch)
        {
            spriteBatch.Draw(texture, Position, Color.White);
        }
    }
}
