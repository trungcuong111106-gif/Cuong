using Microsoft.Xna.Framework;
using Microsoft.Xna.Framework.Graphics;
using Microsoft.Xna.Framework.Input;

namespace MarioGame
{
    public class MarioGame : Microsoft.Xna.Framework.Game
    {
        private GraphicsDeviceManager graphics;
        private SpriteBatch spriteBatch;
        private Player player;
        private Level level;
        private Camera camera;
        private SpriteFont font;

        public MarioGame()
        {
            graphics = new GraphicsDeviceManager(this);
            Content.RootDirectory = "Content";
            IsMouseVisible = true;
        }

        protected override void Initialize()
        {
            graphics.PreferredBackBufferWidth = 1200;
            graphics.PreferredBackBufferHeight = 700;
            graphics.ApplyChanges();

            player = new Player(new Vector2(100, 400));
            level = new Level();
            camera = new Camera();

            base.Initialize();
        }

        protected override void LoadContent()
        {
            spriteBatch = new SpriteBatch(GraphicsDevice);
            player.LoadContent(Content, GraphicsDevice);
            level.LoadContent(Content, GraphicsDevice);
            font = Content.Load<SpriteFont>("Arial");
        }

        protected override void Update(GameTime gameTime)
        {
            if (GamePad.GetState(PlayerIndex.One).Buttons.Back == ButtonState.Pressed || 
                Keyboard.GetState().IsKeyDown(Keys.Escape))
                Exit();

            player.Update(gameTime, level);
            camera.Update(player.Position, graphics.PreferredBackBufferWidth, graphics.PreferredBackBufferHeight);

            base.Update(gameTime);
        }

        protected override void Draw(GameTime gameTime)
        {
            GraphicsDevice.Clear(Color.CornflowerBlue);

            spriteBatch.Begin(transformMatrix: camera.GetViewMatrix());
            level.Draw(spriteBatch);
            player.Draw(spriteBatch);
            spriteBatch.End();

            // Draw UI
            spriteBatch.Begin();
            spriteBatch.DrawString(font, "Mario Game - Controls: A/D or Arrow Keys to move, Space to jump", new Vector2(10, 10), Color.Black);
            spriteBatch.DrawString(font, $"Position: {player.Position.X:F0}, {player.Position.Y:F0}", new Vector2(10, 30), Color.Black);
            spriteBatch.End();

            base.Draw(gameTime);
        }
    }
}
