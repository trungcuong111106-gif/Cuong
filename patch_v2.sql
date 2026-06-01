-- ============================================================
-- PATCH V2 – Tương thích MySQL 5.7+ / 8.x
-- Chạy SAU khi đã có database_full.sql
-- ============================================================

USE `game_ailatiphu`;

-- ============================================================
-- BƯỚC 1: Thêm 3 chủ đề mới
-- ============================================================
INSERT IGNORE INTO `linhvuc` (`id`,`tenlinhvuc`,`icon`,`mota`) VALUES
(6,'Địa lý','🌍','Quốc gia • Địa danh • Địa hình thế giới'),
(7,'Văn học','📚','Tác phẩm • Tác giả • Văn học thế giới & Việt Nam'),
(8,'Tổng hợp','🎯','Gộp tất cả chủ đề, độ khó tăng dần');

-- ============================================================
-- BƯỚC 2: Thêm cột mới vào luotchoi (dùng Procedure để tránh lỗi)
-- ============================================================
DROP PROCEDURE IF EXISTS `sp_patch_columns`;

DELIMITER $$
CREATE PROCEDURE `sp_patch_columns`()
BEGIN
  -- Thêm cột da_hoi_sinh nếu chưa có
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'luotchoi'
      AND COLUMN_NAME  = 'da_hoi_sinh'
  ) THEN
    ALTER TABLE `luotchoi`
      ADD COLUMN `da_hoi_sinh` tinyint(1) NOT NULL DEFAULT 0
      COMMENT '1=đã dùng hồi sinh';
  END IF;

  -- Thêm cột loai_choi nếu chưa có
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'luotchoi'
      AND COLUMN_NAME  = 'loai_choi'
  ) THEN
    ALTER TABLE `luotchoi`
      ADD COLUMN `loai_choi` enum('normal','event') NOT NULL DEFAULT 'normal';
  END IF;
END$$
DELIMITER ;

CALL `sp_patch_columns`();
DROP PROCEDURE IF EXISTS `sp_patch_columns`;

-- ============================================================
-- BƯỚC 3: Tạo bảng item người dùng (hồi sinh)
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_items` (
  `id`          int           NOT NULL AUTO_INCREMENT,
  `taikhoan_id` int           NOT NULL,
  `item_type`   varchar(50)   NOT NULL COMMENT 'revive, hint...',
  `so_luong`    int           NOT NULL DEFAULT 0,
  `cap_nhat`    datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_item` (`taikhoan_id`,`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BƯỚC 4: Tạo bảng Merge Game
-- ============================================================
CREATE TABLE IF NOT EXISTS `merge_leaderboard` (
  `id`          int  NOT NULL AUTO_INCREMENT,
  `taikhoan_id` int  NOT NULL,
  `best_score`  int  NOT NULL DEFAULT 0,
  `best_tile`   int  NOT NULL DEFAULT 2,
  `cap_nhat`    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tk` (`taikhoan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BƯỚC 5: Câu hỏi ĐỊA LÝ (linhvuc_id=6) – 100 câu
-- ============================================================
INSERT INTO `cauhoi` (`noidung`,`linhvuc_id`,`mucdo`,`thoigian`) VALUES
-- Dễ (1-20)
('Thủ đô của Việt Nam là thành phố nào?',6,1,30),
('Nước nào có diện tích lớn nhất thế giới?',6,1,30),
('Sông nào dài nhất thế giới?',6,1,30),
('Núi nào cao nhất thế giới?',6,1,30),
('Thủ đô của Pháp là gì?',6,1,30),
('Châu lục nào lớn nhất thế giới?',6,1,30),
('Đại dương nào lớn nhất thế giới?',6,1,30),
('Quốc gia nào có dân số đông nhất thế giới?',6,1,30),
('Thủ đô của Nhật Bản là gì?',6,1,30),
('Sa mạc Sahara nằm ở châu lục nào?',6,1,30),
('Quốc gia nào nhỏ nhất thế giới?',6,1,30),
('Thác nước Victoria nằm ở châu lục nào?',6,1,30),
('Hồ nước ngọt lớn nhất thế giới là hồ nào?',6,1,30),
('Thủ đô của Úc là thành phố nào?',6,1,30),
('Kênh đào nào nối Đại Tây Dương và Thái Bình Dương?',6,1,30),
('Dãy núi nào dài nhất thế giới?',6,1,30),
('Sông Amazon chảy qua châu lục nào?',6,1,30),
('Thủ đô của Brazil là gì?',6,1,30),
('Đảo nào lớn nhất thế giới?',6,1,30),
('Quốc gia nào có đường bờ biển dài nhất thế giới?',6,1,30),
-- Trung bình (21-50)
('Quốc gia nào có số múi giờ nhiều nhất?',6,2,25),
('Điểm thấp nhất trên mặt đất Trái Đất là ở đâu?',6,2,25),
('Thủ đô nào cao nhất thế giới so với mực nước biển?',6,2,25),
('Quốc gia nào có nhiều hồ nước ngọt nhất thế giới?',6,2,25),
('Thành phố nào đông dân nhất thế giới?',6,2,25),
('Vịnh nào lớn nhất thế giới?',6,2,25),
('Quốc gia nào chiếm toàn bộ một châu lục?',6,2,25),
('Sông Mekong bắt nguồn từ quốc gia nào?',6,2,25),
('Thành phố cổ Petra nằm ở quốc gia nào?',6,2,25),
('Núi lửa Kilimanjaro nằm ở quốc gia nào?',6,2,25),
('Biển Chết nằm giữa Jordan và quốc gia nào?',6,2,25),
('Quốc gia nào có mật độ dân số cao nhất thế giới?',6,2,25),
('Quốc gia nào xuất khẩu dầu mỏ nhiều nhất?',6,2,25),
('Hoang mạc lạnh lớn nhất thế giới là gì?',6,2,25),
('Thành phố nào được gọi là "Thành phố không bao giờ ngủ"?',6,2,25),
('Ngọn núi nào cao nhất châu Phi?',6,2,25),
('Đường kinh tuyến gốc 0 độ đi qua thành phố nào ở Anh?',6,2,25),
('Quốc gia nào có diện tích rừng Amazon lớn nhất?',6,2,25),
('Đảo quốc nào gồm hơn 7.000 hòn đảo ở Đông Nam Á?',6,2,25),
('Thành phố Amsterdam nằm ở quốc gia nào?',6,2,25),
('Biển Caspian là hồ hay biển thực sự?',6,2,25),
('Núi Fuji nằm ở đảo nào của Nhật Bản?',6,2,25),
('Quốc gia nào có biểu tượng lá phong?',6,2,25),
('Eo biển nào ngăn cách châu Âu và châu Phi?',6,2,25),
('Thành phố Timbuktu nằm ở quốc gia nào?',6,2,25),
('Hồ Baikal chứa khoảng bao nhiêu % nước ngọt thế giới?',6,2,25),
('Quốc gia nào có hình dạng "chiếc ủng"?',6,2,25),
('Kinh tuyến đổi ngày quốc tế nằm ở kinh độ bao nhiêu?',6,2,25),
('Hồ Titicaca nằm giữa hai quốc gia nào?',6,2,25),
('Quốc gia nào có đường biên giới đất liền dài nhất thế giới?',6,2,25),
-- Khó (51-80)
('Điểm cực Nam của châu Mỹ là mũi đất nào?',6,3,20),
('Sông Volga đổ vào biển nào?',6,3,20),
('Quần đảo Galapagos thuộc quốc gia nào?',6,3,20),
('Thành phố nào nằm trên cả hai châu lục?',6,3,20),
('Độ sâu tối đa của Rãnh Mariana là khoảng bao nhiêu km?',6,3,20),
('Quốc gia nào không có biển và bị bao quanh bởi các quốc gia cũng không có biển?',6,3,20),
('Thành phố Venice nằm trên bao nhiêu hòn đảo nhỏ?',6,3,20),
('Núi K2 nằm ở biên giới giữa hai quốc gia nào?',6,3,20),
('Quốc gia nào có tên trong tiếng Tây Ban Nha là "Ecuador"?',6,3,20),
('Rừng Daintree nằm ở quốc gia nào?',6,3,20),
('Núi lửa Krakatoa nổi tiếng nằm ở quốc gia nào?',6,3,20),
('Sông Congo là sông sâu nhất thế giới, nằm ở châu lục nào?',6,3,20),
('Thành phố cảng Valparaíso nằm ở quốc gia nào?',6,3,20),
('Quần đảo Maldives nằm ở đại dương nào?',6,3,20),
('Núi Blanc (Mont Blanc) thuộc biên giới hai quốc gia nào?',6,3,20),
('Dãy núi Ural là ranh giới tự nhiên giữa châu Âu và châu nào?',6,3,20),
('Thành phố Ulaanbaatar là thủ đô của nước nào?',6,3,20),
('Eo biển Drake nằm giữa châu Nam Cực và quốc gia nào?',6,3,20),
('Đảo Borneo được chia sẻ bởi bao nhiêu quốc gia?',6,3,20),
('Biển Laptev nằm ở đại dương nào?',6,3,20),
('Quốc gia nào tiếp giáp với nhiều quốc gia nhất thế giới?',6,3,20),
('Thành phố Almaty nằm ở quốc gia nào?',6,3,20),
('Vùng lãnh thổ nào có diện tích lớn nhất thế giới nhưng không phải quốc gia độc lập?',6,3,20),
('Hồ Superior thuộc về hai quốc gia nào?',6,3,20),
('Sông Rhine chảy qua bao nhiêu quốc gia châu Âu?',6,3,20),
('Biển Đỏ thông ra đại dương qua eo biển nào?',6,3,20),
('Quần đảo nào thuộc Tây Ban Nha nhưng nằm gần bờ biển Maroc?',6,3,20),
('Thành phố nào được UNESCO công nhận là "Thành phố Hòa bình"?',6,3,20),
('Eo biển Bering ngăn cách hai quốc gia nào?',6,3,20),
('Vùng Patagonia nằm ở phần phía nam của quốc gia nào?',6,3,20),
-- Rất khó (81-95)
('Độ cao trung bình của châu Nam Cực là khoảng bao nhiêu mét?',6,4,15),
('Quốc gia nào có nhiều múi giờ nhất mà không liên tục?',6,4,15),
('Điểm xa nhất so với tâm Trái Đất là đỉnh núi nào?',6,4,15),
('Đường phân chia EEZ có phạm vi bao nhiêu hải lý?',6,4,15),
('Quốc gia nào nằm hoàn toàn trong lãnh thổ của một quốc gia khác?',6,4,15),
('Biển Aral đã thu hẹp bao nhiêu phần trăm diện tích từ thập niên 1960?',6,4,15),
('Đảo quốc nào có mật độ dân số cao nhất thế giới?',6,4,15),
('Quốc gia nào có đường bờ biển dài nhất tính theo tỷ lệ diện tích?',6,4,15),
('Khu vực địa lý nào được gọi là "Vành đai lửa Thái Bình Dương"?',6,4,15),
('Thành phố nào là trung tâm địa lý của châu Âu theo tính toán?',6,4,15),
('Vùng Trung Đông gồm bao nhiêu quốc gia theo định nghĩa rộng?',6,4,15),
('Cao nguyên Tây Tạng có độ cao trung bình khoảng bao nhiêu mét?',6,4,15),
('Dãy núi nào ngăn cách Ấn Độ với phần còn lại của châu Á?',6,4,15),
('Quốc gia nào có tỷ lệ diện tích mặt nước nhiều nhất so với diện tích đất?',6,4,15),
('Sông nào có lưu lượng nước lớn nhất thế giới?',6,4,15),
-- Siêu khó (96-100)
('Độ dài chính xác của đường xích đạo Trái Đất là bao nhiêu km?',6,5,10),
('Đỉnh núi nào được tính là cao nhất thế giới nếu đo từ chân núi dưới đáy biển?',6,5,10),
('Vùng lãnh thổ nào có tọa độ 90 độ Bắc?',6,5,10),
('Quốc gia nào có điểm thấp nhất và điểm cao nhất đều nằm trong lãnh thổ?',6,5,10),
('Thềm lục địa Sunda kết nối những đảo nào của Indonesia với đất liền Đông Nam Á?',6,5,10);

-- ============================================================
-- BƯỚC 6: Câu hỏi VĂN HỌC (linhvuc_id=7) – 100 câu
-- ============================================================
INSERT INTO `cauhoi` (`noidung`,`linhvuc_id`,`mucdo`,`thoigian`) VALUES
-- Dễ (1-20)
('Tác phẩm "Romeo và Juliet" do ai viết?',7,1,30),
('Truyện Kiều là tác phẩm của nhà văn Việt Nam nào?',7,1,30),
('Tác phẩm "Harry Potter" do ai sáng tác?',7,1,30),
('"Nam quốc sơn hà" tương truyền do ai viết?',7,1,30),
('Nhân vật Sherlock Holmes do ai tạo ra?',7,1,30),
('Tiểu thuyết "1984" do ai viết?',7,1,30),
('Tác phẩm "Dế Mèn Phiêu Lưu Ký" do ai viết?',7,1,30),
('"Don Quixote" là tác phẩm của nhà văn nào?',7,1,30),
('Ai là tác giả của "Những người khốn khổ" (Les Misérables)?',7,1,30),
('Tác phẩm "Chiến tranh và Hòa bình" do ai viết?',7,1,30),
('"Tắt đèn" là tác phẩm của nhà văn Việt Nam nào?',7,1,30),
('Truyện ngắn "Chí Phèo" do ai viết?',7,1,30),
('Ai là tác giả của "Bà Bovary"?',7,1,30),
('"Lão Hạc" là truyện ngắn của nhà văn nào?',7,1,30),
('Tác phẩm "Hamlet" thuộc thể loại gì?',7,1,30),
('"Số đỏ" là tiểu thuyết của nhà văn Việt Nam nào?',7,1,30),
('Tác phẩm "Iliad" và "Odyssey" tương truyền do ai sáng tác?',7,1,30),
('Nhân vật "Robinson Crusoe" xuất hiện trong tiểu thuyết của ai?',7,1,30),
('"Truyện Lục Vân Tiên" do ai viết?',7,1,30),
('Tác phẩm "Dế Mèn Phiêu Lưu Ký" viết về loài vật nào?',7,1,30),
-- Trung bình (21-50)
('Giải Nobel Văn học đầu tiên được trao năm nào?',7,2,25),
('Ai là tác giả của "Trăm năm cô đơn"?',7,2,25),
('Phong trào Thơ Mới ở Việt Nam nở rộ vào thập niên nào?',7,2,25),
('"Vợ chồng A Phủ" là tác phẩm của nhà văn nào?',7,2,25),
('"Đất rừng phương Nam" là tiểu thuyết của ai?',7,2,25),
('Nhà thơ Xuân Diệu được mệnh danh là gì?',7,2,25),
('"The Great Gatsby" do ai viết?',7,2,25),
('Tác phẩm nào mở đầu bằng câu "Call me Ishmael"?',7,2,25),
('Tiểu thuyết "Anna Karenina" do ai sáng tác?',7,2,25),
('Nhà văn Nam Cao tên thật là gì?',7,2,25),
('"Pride and Prejudice" do ai viết?',7,2,25),
('Nhà thơ Nguyễn Du sinh năm nào?',7,2,25),
('"The Alchemist" (Nhà giả kim) do ai viết?',7,2,25),
('"Bỉ vỏ" là tiểu thuyết của nhà văn Việt Nam nào?',7,2,25),
('"Mắt biếc" là tiểu thuyết của ai?',7,2,25),
('"Cánh đồng bất tận" do nhà văn nào viết?',7,2,25),
('"Crime and Punishment" do ai viết?',7,2,25),
('Truyện "Tấm Cám" thuộc thể loại văn học dân gian nào?',7,2,25),
('Giải Nobel Văn học 2023 được trao cho ai?',7,2,25),
('"Cung oán ngâm khúc" do ai viết?',7,2,25),
('Thể thơ haiku xuất xứ từ nước nào?',7,2,25),
('Nhà văn Kafka nổi tiếng với tác phẩm nào viết về biến thành côn trùng?',7,2,25),
('"Ông già và biển cả" là tác phẩm của ai?',7,2,25),
('"Chinh phụ ngâm" do ai dịch sang chữ Nôm nổi tiếng nhất?',7,2,25),
('Nhà văn Nguyễn Tuân nổi tiếng với tập tùy bút nào?',7,2,25),
('"Bắt trẻ đồng xanh" (The Catcher in the Rye) do ai viết?',7,2,25),
('Tập thơ "Nhật ký trong tù" do ai viết?',7,2,25),
('"Tây du ký" do ai viết?',7,2,25),
('Thể thơ sonnet truyền thống có bao nhiêu câu?',7,2,25),
('"Tam quốc diễn nghĩa" do ai biên soạn?',7,2,25),
-- Khó (51-80)
('Kỹ thuật "dòng ý thức" trong văn học do ai tiên phong?',7,3,20),
('"Hồng lâu mộng" là tiểu thuyết của ai?',7,3,20),
('Nhà thơ Pushkin được mệnh danh là gì?',7,3,20),
('Tác phẩm "Waiting for Godot" thuộc trào lưu kịch nào?',7,3,20),
('Phong trào Romanticism trong văn học châu Âu nở rộ vào thế kỷ nào?',7,3,20),
('"Aleph" là tập truyện ngắn của nhà văn nào?',7,3,20),
('Tác phẩm "Lolita" gây tranh cãi do ai viết?',7,3,20),
('Nhà thơ Emily Dickinson viết phần lớn thơ trong hoàn cảnh nào?',7,3,20),
('"Đèn không hắt bóng" là tác phẩm của nhà văn Nhật Bản nào?',7,3,20),
('Trào lưu "Văn học hiện sinh" gắn với triết gia-nhà văn nào của Pháp?',7,3,20),
('"Tây du ký" kể về hành trình đến xứ sở nào để lấy kinh?',7,3,20),
('Nhà thơ Hàn Mặc Tử nổi tiếng với những bài thơ thuộc phong trào nào?',7,3,20),
('Trường phái văn học Hiện thực huyền ảo Latin gắn với tên nhà văn nào?',7,3,20),
('"Kafka bên bờ biển" do ai viết?',7,3,20),
('Nhà thơ Tố Hữu nổi tiếng với thể thơ nào?',7,3,20),
('"Đất nước" là bài thơ nổi tiếng của nhà thơ Việt Nam nào?',7,3,20),
('Nhà văn Nam Phi Nadine Gordimer nổi tiếng với đề tài gì?',7,3,20),
('Giải Nobel Văn học 2010 được trao cho nhà văn nào?',7,3,20),
('"Kim Vân Kiều truyện" nguyên bản của Trung Quốc do ai viết?',7,3,20),
('Nhà văn Nguyễn Huy Thiệp nổi tiếng với thể loại gì?',7,3,20),
('"Sống mòn" là tiểu thuyết của nhà văn nào?',7,3,20),
('Bài thơ "Đây thôn Vĩ Dạ" do nhà thơ nào viết?',7,3,20),
('"Người đọc" (The Reader) của Bernhard Schlink thuộc nền văn học nào?',7,3,20),
('Nhà thơ Chế Lan Viên nổi tiếng với tập thơ nào thời kỳ kháng chiến?',7,3,20),
('"Bắc Sơn" là vở kịch của nhà văn Việt Nam nào?',7,3,20),
('Ai viết "Những linh hồn chết" (Dead Souls)?',7,3,20),
('"Pedro Páramo" là tiểu thuyết của nhà văn nào?',7,3,20),
('Nhà thơ Walt Whitman nổi tiếng với tập thơ nào?',7,3,20),
('Giải Pulitzer Văn học là giải thưởng của quốc gia nào?',7,3,20),
('"Chí Phèo" thuộc thể loại văn học nào?',7,3,20),
-- Rất khó (81-95)
('Lý thuyết "Cái chết của tác giả" do nhà phê bình nào đề xuất?',7,4,15),
('Trường phái Deconstruction trong văn học gắn với tên ai?',7,4,15),
('Nhà thơ Stéphane Mallarmé đại diện cho trào lưu thơ nào?',7,4,15),
('Khái niệm "Intertextuality" trong lý luận văn học do ai đề xuất?',7,4,15),
('Tác phẩm "In Search of Lost Time" do ai viết và có bao nhiêu tập?',7,4,15),
('"Truyền kỳ mạn lục" do ai viết?',7,4,15),
('Phong trào Oulipo trong văn học Pháp có đặc điểm gì?',7,4,15),
('"Âm thanh và cuồng nộ" (The Sound and the Fury) do ai viết?',7,4,15),
('Nhà thơ Paul Celan viết bài thơ "Todesfuge" về đề tài gì?',7,4,15),
('Trào lưu "Nouveau Roman" ở Pháp gắn với nhà văn nào?',7,4,15),
('Lý luận "Defamiliarization" do nhà lý luận nào của Hình thức luận Nga đề xuất?',7,4,15),
('Nhà văn William Faulkner sử dụng kỹ thuật nào đặc trưng trong tác phẩm?',7,4,15),
('"Finnegans Wake" của James Joyce được viết theo phong cách đặc biệt nào?',7,4,15),
('Lý thuyết "Carnival" của Bakhtin áp dụng vào phân tích văn học như thế nào?',7,4,15),
('Nhà thơ Gerard Manley Hopkins phát triển kỹ thuật thơ nào?',7,4,15),
-- Siêu khó (96-100)
('Khái niệm "Heteroglossia" trong lý luận Bakhtin có nghĩa là gì?',7,5,10),
('Tác phẩm "The Recognitions" của William Gaddis được xem là khởi đầu của trào lưu nào?',7,5,10),
('Nhà thơ Việt Nam đầu tiên được dịch và giới thiệu rộng rãi tại phương Tây là ai?',7,5,10),
('Khái niệm "Sprung rhythm" do nhà thơ nào phát triển?',7,5,10),
('Tác phẩm dài nhất trong văn học thế giới theo số chữ là tác phẩm nào?',7,5,10);

-- ============================================================
-- BƯỚC 7: ĐÁP ÁN ĐỊA LÝ
-- ============================================================
-- Lấy ID bắt đầu của câu hỏi địa lý
SET @g = (SELECT MIN(id) FROM cauhoi WHERE linhvuc_id=6);

INSERT INTO `dapan` (`cauhoi_id`,`noidung`,`ladapan_dung`) VALUES
(@g+0,'Hà Nội',1),(@g+0,'TP. Hồ Chí Minh',0),(@g+0,'Đà Nẵng',0),(@g+0,'Huế',0),
(@g+1,'Nga',1),(@g+1,'Canada',0),(@g+1,'Trung Quốc',0),(@g+1,'Mỹ',0),
(@g+2,'Sông Nile',1),(@g+2,'Sông Amazon',0),(@g+2,'Sông Yangtze',0),(@g+2,'Sông Mississippi',0),
(@g+3,'Everest',1),(@g+3,'K2',0),(@g+3,'Kangchenjunga',0),(@g+3,'Lhotse',0),
(@g+4,'Paris',1),(@g+4,'Lyon',0),(@g+4,'Marseille',0),(@g+4,'Nice',0),
(@g+5,'Châu Á',1),(@g+5,'Châu Phi',0),(@g+5,'Châu Mỹ',0),(@g+5,'Châu Âu',0),
(@g+6,'Thái Bình Dương',1),(@g+6,'Đại Tây Dương',0),(@g+6,'Ấn Độ Dương',0),(@g+6,'Bắc Băng Dương',0),
(@g+7,'Ấn Độ',1),(@g+7,'Trung Quốc',0),(@g+7,'Mỹ',0),(@g+7,'Indonesia',0),
(@g+8,'Tokyo',1),(@g+8,'Osaka',0),(@g+8,'Kyoto',0),(@g+8,'Hiroshima',0),
(@g+9,'Châu Phi',1),(@g+9,'Châu Á',0),(@g+9,'Châu Mỹ',0),(@g+9,'Châu Úc',0),
(@g+10,'Vatican',1),(@g+10,'Monaco',0),(@g+10,'San Marino',0),(@g+10,'Liechtenstein',0),
(@g+11,'Châu Phi',1),(@g+11,'Châu Mỹ',0),(@g+11,'Châu Á',0),(@g+11,'Châu Úc',0),
(@g+12,'Hồ Superior',1),(@g+12,'Hồ Baikal',0),(@g+12,'Hồ Caspian',0),(@g+12,'Hồ Victoria',0),
(@g+13,'Canberra',1),(@g+13,'Sydney',0),(@g+13,'Melbourne',0),(@g+13,'Brisbane',0),
(@g+14,'Kênh đào Panama',1),(@g+14,'Kênh đào Suez',0),(@g+14,'Kênh đào Corinth',0),(@g+14,'Kênh đào Kiel',0),
(@g+15,'Dãy Andes',1),(@g+15,'Dãy Himalaya',0),(@g+15,'Dãy Rocky',0),(@g+15,'Dãy Alps',0),
(@g+16,'Nam Mỹ',1),(@g+16,'Bắc Mỹ',0),(@g+16,'Châu Phi',0),(@g+16,'Châu Á',0),
(@g+17,'Brasília',1),(@g+17,'Rio de Janeiro',0),(@g+17,'São Paulo',0),(@g+17,'Salvador',0),
(@g+18,'Greenland',1),(@g+18,'New Guinea',0),(@g+18,'Borneo',0),(@g+18,'Madagascar',0),
(@g+19,'Canada',1),(@g+19,'Nga',0),(@g+19,'Na Uy',0),(@g+19,'Australia',0),
(@g+20,'Pháp (13 múi giờ)',1),(@g+20,'Nga',0),(@g+20,'Mỹ',0),(@g+20,'Anh',0),
(@g+21,'Biển Chết (Dead Sea)',1),(@g+21,'Thung lũng Chết',0),(@g+21,'Rãnh Mariana',0),(@g+21,'Hồ Assal',0),
(@g+22,'La Paz, Bolivia',1),(@g+22,'Quito, Ecuador',0),(@g+22,'Thimphu, Bhutan',0),(@g+22,'Addis Ababa',0),
(@g+23,'Canada',1),(@g+23,'Phần Lan',0),(@g+23,'Nga',0),(@g+23,'Brazil',0),
(@g+24,'Tokyo (Greater Tokyo)',1),(@g+24,'Delhi',0),(@g+24,'Thượng Hải',0),(@g+24,'Jakarta',0),
(@g+25,'Vịnh Bengal',1),(@g+25,'Vịnh Mexico',0),(@g+25,'Vịnh Ba Tư',0),(@g+25,'Vịnh Hudson',0),
(@g+26,'Australia',1),(@g+26,'Nga',0),(@g+26,'Brazil',0),(@g+26,'Canada',0),
(@g+27,'Trung Quốc (Tây Tạng)',1),(@g+27,'Ấn Độ',0),(@g+27,'Myanmar',0),(@g+27,'Lào',0),
(@g+28,'Jordan',1),(@g+28,'Morocco',0),(@g+28,'Tunisia',0),(@g+28,'Thổ Nhĩ Kỳ',0),
(@g+29,'Tanzania',1),(@g+29,'Kenya',0),(@g+29,'Ethiopia',0),(@g+29,'Uganda',0),
(@g+30,'Israel',1),(@g+30,'Palestine',0),(@g+30,'Syria',0),(@g+30,'Lebanon',0),
(@g+31,'Singapore',1),(@g+31,'Bangladesh',0),(@g+31,'Monaco',0),(@g+31,'Bahrain',0),
(@g+32,'Saudi Arabia',1),(@g+32,'Nga',0),(@g+32,'Mỹ',0),(@g+32,'UAE',0),
(@g+33,'Sa mạc Nam Cực',1),(@g+33,'Sa mạc Gobi',0),(@g+33,'Sa mạc Sahara',0),(@g+33,'Sa mạc Arabian',0),
(@g+34,'New York',1),(@g+34,'Las Vegas',0),(@g+34,'Chicago',0),(@g+34,'Los Angeles',0),
(@g+35,'Kilimanjaro',1),(@g+35,'Kenya Mountain',0),(@g+35,'Ruwenzori',0),(@g+35,'Atlas',0),
(@g+36,'Greenwich, London',1),(@g+36,'Edinburgh',0),(@g+36,'Bristol',0),(@g+36,'Oxford',0),
(@g+37,'Brazil',1),(@g+37,'Colombia',0),(@g+37,'Peru',0),(@g+37,'Venezuela',0),
(@g+38,'Philippines',1),(@g+38,'Indonesia',0),(@g+38,'Nhật Bản',0),(@g+38,'Malaysia',0),
(@g+39,'Hà Lan',1),(@g+39,'Bỉ',0),(@g+39,'Đức',0),(@g+39,'Đan Mạch',0),
(@g+40,'Hồ lớn nhất thế giới về diện tích (không phải hồ nước ngọt)',1),(@g+40,'Hồ nước ngọt',0),(@g+40,'Vịnh biển',0),(@g+40,'Biển nội địa',0),
(@g+41,'Đảo Honshu',1),(@g+41,'Kyushu',0),(@g+41,'Shikoku',0),(@g+41,'Hokkaido',0),
(@g+42,'Canada',1),(@g+42,'Úc',0),(@g+42,'Thụy Điển',0),(@g+42,'Nhật Bản',0),
(@g+43,'Eo biển Gibraltar',1),(@g+43,'Eo biển Sicily',0),(@g+43,'Eo biển Messina',0),(@g+43,'Eo biển Dardanelles',0),
(@g+44,'Mali',1),(@g+44,'Niger',0),(@g+44,'Mauritania',0),(@g+44,'Burkina Faso',0),
(@g+45,'Khoảng 20%',1),(@g+45,'5%',0),(@g+45,'10%',0),(@g+45,'30%',0),
(@g+46,'Ý',1),(@g+46,'Tây Ban Nha',0),(@g+46,'Hy Lạp',0),(@g+46,'Bồ Đào Nha',0),
(@g+47,'180 độ',1),(@g+47,'90 độ',0),(@g+47,'270 độ',0),(@g+47,'0 độ',0),
(@g+48,'Bolivia và Peru',1),(@g+48,'Chile và Bolivia',0),(@g+48,'Peru và Ecuador',0),(@g+48,'Argentina và Chile',0),
(@g+49,'Canada và Mỹ',1),(@g+49,'Mỹ và Mexico',0),(@g+49,'Nga và Kazakhstan',0),(@g+49,'Trung Quốc và Nga',0),
(@g+50,'Mũi Horn (Cape Horn)',1),(@g+50,'Mũi Hảo Vọng',0),(@g+50,'Mũi Farewell',0),(@g+50,'Tierra del Fuego',0),
(@g+51,'Biển Caspian',1),(@g+51,'Biển Đen',0),(@g+51,'Biển Aral',0),(@g+51,'Biển Azov',0),
(@g+52,'Ecuador',1),(@g+52,'Colombia',0),(@g+52,'Chile',0),(@g+52,'Peru',0),
(@g+53,'Istanbul (Thổ Nhĩ Kỳ)',1),(@g+53,'Moscow',0),(@g+53,'Cairo',0),(@g+53,'Athens',0),
(@g+54,'Khoảng 11 km',1),(@g+54,'8 km',0),(@g+54,'15 km',0),(@g+54,'7 km',0),
(@g+55,'Liechtenstein và Uzbekistan',1),(@g+55,'Chỉ Uzbekistan',0),(@g+55,'Bolivia',0),(@g+55,'Nepal',0),
(@g+56,'118 hòn đảo nhỏ',1),(@g+56,'50 đảo',0),(@g+56,'200 đảo',0),(@g+56,'500 đảo',0),
(@g+57,'Pakistan và Trung Quốc',1),(@g+57,'Nepal và Trung Quốc',0),(@g+57,'Ấn Độ và Pakistan',0),(@g+57,'Afghanistan và Pakistan',0),
(@g+58,'Ecuador (tên = xích đạo)',1),(@g+58,'Kenya',0),(@g+58,'Colombia',0),(@g+58,'Brazil',0),
(@g+59,'Australia',1),(@g+59,'Brazil',0),(@g+59,'Congo',0),(@g+59,'New Zealand',0),
(@g+60,'Indonesia',1),(@g+60,'Philippines',0),(@g+60,'Malaysia',0),(@g+60,'Nhật Bản',0),
(@g+61,'Châu Á',1),(@g+61,'Châu Âu',0),(@g+61,'Châu Phi',0),(@g+61,'Bắc Cực',0),
(@g+62,'Mông Cổ',1),(@g+62,'Kyrgyzstan',0),(@g+62,'Uzbekistan',0),(@g+62,'Kazakhstan',0),
(@g+63,'Greenland (thuộc Đan Mạch)',1),(@g+63,'Alaska',0),(@g+63,'Papua New Guinea',0),(@g+63,'Tây Tạng',0),
(@g+64,'Canada và Mỹ (hồ Superior)',1),(@g+64,'Nga và Phần Lan',0),(@g+64,'Mỹ và Mexico',0),(@g+64,'Canada và Nga',0),
(@g+65,'6 quốc gia',1),(@g+65,'4 quốc gia',0),(@g+65,'5 quốc gia',0),(@g+65,'8 quốc gia',0),
(@g+66,'Eo Bab-el-Mandeb ra Biển Arab/Ấn Độ Dương',1),(@g+66,'Eo Suez',0),(@g+66,'Eo Hormuz',0),(@g+66,'Eo Aden',0),
(@g+67,'Quần đảo Canary',1),(@g+67,'Quần đảo Azores',0),(@g+67,'Quần đảo Madeira',0),(@g+67,'Quần đảo Balearic',0),
(@g+68,'Hiroshima, Nhật Bản',1),(@g+68,'Geneva, Thụy Sĩ',0),(@g+68,'Sarajevo, Bosnia',0),(@g+68,'Vienna, Áo',0),
(@g+69,'Mỹ và Nga',1),(@g+69,'Canada và Nga',0),(@g+69,'Mỹ và Canada',0),(@g+69,'Nga và Nhật Bản',0),
(@g+70,'Argentina',1),(@g+70,'Chile',0),(@g+70,'Brazil',0),(@g+70,'Uruguay',0),
(@g+71,'Khoảng 2.300m',1),(@g+71,'500m',0),(@g+71,'5.000m',0),(@g+71,'1.000m',0),
(@g+72,'Pháp (13 múi giờ bao gồm hải ngoại)',1),(@g+72,'Mỹ',0),(@g+72,'Anh',0),(@g+72,'Nga',0),
(@g+73,'Chimborazo (Ecuador)',1),(@g+73,'Everest',0),(@g+73,'Mauna Kea',0),(@g+73,'K2',0),
(@g+74,'200 hải lý',1),(@g+74,'12 hải lý',0),(@g+74,'100 hải lý',0),(@g+74,'500 hải lý',0),
(@g+75,'Lesotho và San Marino',1),(@g+75,'Chỉ Vatican',0),(@g+75,'Monaco',0),(@g+75,'Andorra',0),
(@g+76,'Mỹ (Death Valley thấp nhất, Denali cao nhất)',1),(@g+76,'Trung Quốc',0),(@g+76,'Nga',0),(@g+76,'Ấn Độ',0),
(@g+77,'Mauna Kea (Hawaii)',1),(@g+77,'Everest',0),(@g+77,'Mont Blanc',0),(@g+77,'K2',0),
(@g+78,'Vùng băng Bắc Cực (không có đất liền)',1),(@g+78,'Alaska',0),(@g+78,'Canada',0),(@g+78,'Greenland',0),
(@g+79,'Trung Quốc (14 quốc gia)',1),(@g+79,'Nga',0),(@g+79,'Brazil',0),(@g+79,'Mỹ',0),
(@g+80,'Java, Kalimantan, Sumatra và các đảo khác',1),(@g+80,'Chỉ Java',0),(@g+80,'Chỉ Sumatra',0),(@g+80,'Borneo và Java',0);

-- ============================================================
-- BƯỚC 8: ĐÁP ÁN VĂN HỌC
-- ============================================================
SET @l = (SELECT MIN(id) FROM cauhoi WHERE linhvuc_id=7);

INSERT INTO `dapan` (`cauhoi_id`,`noidung`,`ladapan_dung`) VALUES
(@l+0,'William Shakespeare',1),(@l+0,'Marlowe',0),(@l+0,'Ben Jonson',0),(@l+0,'John Milton',0),
(@l+1,'Nguyễn Du',1),(@l+1,'Nguyễn Trãi',0),(@l+1,'Nguyễn Bỉnh Khiêm',0),(@l+1,'Đặng Trần Côn',0),
(@l+2,'J.K. Rowling',1),(@l+2,'Tolkien',0),(@l+2,'C.S. Lewis',0),(@l+2,'Philip Pullman',0),
(@l+3,'Lý Thường Kiệt',1),(@l+3,'Trần Hưng Đạo',0),(@l+3,'Nguyễn Du',0),(@l+3,'Lê Lợi',0),
(@l+4,'Arthur Conan Doyle',1),(@l+4,'Agatha Christie',0),(@l+4,'Edgar Allan Poe',0),(@l+4,'Dashiell Hammett',0),
(@l+5,'George Orwell',1),(@l+5,'Aldous Huxley',0),(@l+5,'Ray Bradbury',0),(@l+5,'H.G. Wells',0),
(@l+6,'Tô Hoài',1),(@l+6,'Nguyên Hồng',0),(@l+6,'Nam Cao',0),(@l+6,'Nguyễn Tuân',0),
(@l+7,'Miguel de Cervantes',1),(@l+7,'Lope de Vega',0),(@l+7,'Calderón',0),(@l+7,'Quevedo',0),
(@l+8,'Victor Hugo',1),(@l+8,'Émile Zola',0),(@l+8,'Flaubert',0),(@l+8,'Alexandre Dumas',0),
(@l+9,'Leo Tolstoy',1),(@l+9,'Dostoevsky',0),(@l+9,'Turgenev',0),(@l+9,'Chekhov',0),
(@l+10,'Ngô Tất Tố',1),(@l+10,'Nam Cao',0),(@l+10,'Nguyên Hồng',0),(@l+10,'Vũ Trọng Phụng',0),
(@l+11,'Nam Cao',1),(@l+11,'Ngô Tất Tố',0),(@l+11,'Nguyên Hồng',0),(@l+11,'Vũ Trọng Phụng',0),
(@l+12,'Gustave Flaubert',1),(@l+12,'Émile Zola',0),(@l+12,'Stendhal',0),(@l+12,'Balzac',0),
(@l+13,'Nam Cao',1),(@l+13,'Ngô Tất Tố',0),(@l+13,'Tô Hoài',0),(@l+13,'Nguyên Hồng',0),
(@l+14,'Bi kịch (Tragedy)',1),(@l+14,'Hài kịch',0),(@l+14,'Thơ',0),(@l+14,'Truyện ngắn',0),
(@l+15,'Vũ Trọng Phụng',1),(@l+15,'Nam Cao',0),(@l+15,'Nguyễn Công Hoan',0),(@l+15,'Nguyên Hồng',0),
(@l+16,'Homer',1),(@l+16,'Virgil',0),(@l+16,'Hesiod',0),(@l+16,'Pindar',0),
(@l+17,'Daniel Defoe',1),(@l+17,'Jonathan Swift',0),(@l+17,'Henry Fielding',0),(@l+17,'Samuel Richardson',0),
(@l+18,'Nguyễn Đình Chiểu',1),(@l+18,'Nguyễn Du',0),(@l+18,'Trương Vĩnh Ký',0),(@l+18,'Phan Bội Châu',0),
(@l+19,'Dế Mèn',1),(@l+19,'Cào Cào',0),(@l+19,'Bọ Ngựa',0),(@l+19,'Châu Chấu',0),
(@l+20,'1901',1),(@l+20,'1895',0),(@l+20,'1905',0),(@l+20,'1910',0),
(@l+21,'Gabriel García Márquez',1),(@l+21,'Jorge Luis Borges',0),(@l+21,'Pablo Neruda',0),(@l+21,'Mario Vargas Llosa',0),
(@l+22,'Thập niên 1930',1),(@l+22,'1920',0),(@l+22,'1940',0),(@l+22,'1950',0),
(@l+23,'Tô Hoài',1),(@l+23,'Nam Cao',0),(@l+23,'Nguyễn Minh Châu',0),(@l+23,'Nguyễn Khải',0),
(@l+24,'Đoàn Giỏi',1),(@l+24,'Tô Hoài',0),(@l+24,'Nguyễn Thi',0),(@l+24,'Nguyên Ngọc',0),
(@l+25,'"Ông hoàng thơ tình" Việt Nam',1),(@l+25,'Nhà thơ cách mạng',0),(@l+25,'Cha đẻ thơ hiện đại',0),(@l+25,'Nhà thơ lãng mạn',0),
(@l+26,'F. Scott Fitzgerald',1),(@l+26,'Hemingway',0),(@l+26,'Faulkner',0),(@l+26,'Steinbeck',0),
(@l+27,'Moby Dick (Herman Melville)',1),(@l+27,'The Old Man and the Sea',0),(@l+27,'Billy Budd',0),(@l+27,'Heart of Darkness',0),
(@l+28,'Leo Tolstoy',1),(@l+28,'Dostoevsky',0),(@l+28,'Turgenev',0),(@l+28,'Gogol',0),
(@l+29,'Trần Hữu Tri',1),(@l+29,'Trần Đăng Khoa',0),(@l+29,'Hồ Biểu Chánh',0),(@l+29,'Hoàng Văn Hoan',0),
(@l+30,'Jane Austen',1),(@l+30,'Charlotte Brontë',0),(@l+30,'Emily Brontë',0),(@l+30,'George Eliot',0),
(@l+31,'1766',1),(@l+31,'1750',0),(@l+31,'1780',0),(@l+31,'1800',0),
(@l+32,'Paulo Coelho',1),(@l+32,'Jorge Amado',0),(@l+32,'García Márquez',0),(@l+32,'Isabel Allende',0),
(@l+33,'Nguyên Hồng',1),(@l+33,'Vũ Trọng Phụng',0),(@l+33,'Nguyễn Công Hoan',0),(@l+33,'Nam Cao',0),
(@l+34,'Nguyễn Nhật Ánh',1),(@l+34,'Tô Hoài',0),(@l+34,'Nguyễn Ngọc Tư',0),(@l+34,'Dương Thụy',0),
(@l+35,'Nguyễn Ngọc Tư',1),(@l+35,'Nguyễn Nhật Ánh',0),(@l+35,'Phan Thị Vàng Anh',0),(@l+35,'Y Ban',0),
(@l+36,'Fyodor Dostoevsky',1),(@l+36,'Leo Tolstoy',0),(@l+36,'Turgenev',0),(@l+36,'Gogol',0),
(@l+37,'Truyện cổ tích',1),(@l+37,'Thần thoại',0),(@l+37,'Truyền thuyết',0),(@l+37,'Truyện ngụ ngôn',0),
(@l+38,'Jon Fosse (Na Uy)',1),(@l+38,'Annie Ernaux',0),(@l+38,'Abdulrazak Gurnah',0),(@l+38,'Peter Handke',0),
(@l+39,'Nguyễn Gia Thiều',1),(@l+39,'Nguyễn Du',0),(@l+39,'Đặng Trần Côn',0),(@l+39,'Nguyễn Bỉnh Khiêm',0),
(@l+40,'Nhật Bản',1),(@l+40,'Trung Quốc',0),(@l+40,'Hàn Quốc',0),(@l+40,'Việt Nam',0),
(@l+41,'"Hóa thân" (Die Verwandlung)',1),(@l+41,'"Vụ án"',0),(@l+41,'"Lâu đài"',0),(@l+41,'"Mỹ châu"',0),
(@l+42,'Ernest Hemingway',1),(@l+42,'Steinbeck',0),(@l+42,'Faulkner',0),(@l+42,'Fitzgerald',0),
(@l+43,'Đoàn Thị Điểm',1),(@l+43,'Phan Huy Ích',0),(@l+43,'Nguyễn Du',0),(@l+43,'Lê Quý Đôn',0),
(@l+44,'"Vang bóng một thời"',1),(@l+44,'"Sông Đà"',0),(@l+44,'"Hà Nội ta đánh Mỹ giỏi"',0),(@l+44,'"Chữ người tử tù"',0),
(@l+45,'J.D. Salinger',1),(@l+45,'Jack Kerouac',0),(@l+45,'Burroughs',0),(@l+45,'Allen Ginsberg',0),
(@l+46,'Hồ Chí Minh',1),(@l+46,'Tố Hữu',0),(@l+46,'Sóng Hồng',0),(@l+46,'Lê Đức Thọ',0),
(@l+47,'Ngô Thừa Ân',1),(@l+47,'La Quán Trung',0),(@l+47,'Thi Nại Am',0),(@l+47,'Tào Tuyết Cần',0),
(@l+48,'14 câu',1),(@l+48,'12 câu',0),(@l+48,'16 câu',0),(@l+48,'10 câu',0),
(@l+49,'La Quán Trung',1),(@l+49,'Thi Nại Am',0),(@l+49,'Ngô Thừa Ân',0),(@l+49,'Tào Tuyết Cần',0),
(@l+50,'Virginia Woolf',1),(@l+50,'James Joyce',0),(@l+50,'Dorothy Richardson',0),(@l+50,'Faulkner',0),
(@l+51,'Tào Tuyết Cần',1),(@l+51,'La Quán Trung',0),(@l+51,'Thi Nại Am',0),(@l+51,'Ngô Thừa Ân',0),
(@l+52,'"Mặt trời của nền thơ Nga"',1),(@l+52,'"Cha đẻ văn học Nga"',0),(@l+52,'"Vĩ nhân của văn học"',0),(@l+52,'"Thi hào số 1 Nga"',0),
(@l+53,'Kịch phi lý (Theatre of the Absurd)',1),(@l+53,'Kịch hiện thực',0),(@l+53,'Kịch lãng mạn',0),(@l+53,'Kịch biểu hiện',0),
(@l+54,'Thế kỷ 19',1),(@l+54,'Thế kỷ 17',0),(@l+54,'Thế kỷ 18',0),(@l+54,'Thế kỷ 20',0),
(@l+55,'Jorge Luis Borges',1),(@l+55,'Pablo Neruda',0),(@l+55,'Julio Cortázar',0),(@l+55,'García Márquez',0),
(@l+56,'Vladimir Nabokov',1),(@l+56,'Henry Miller',0),(@l+56,'D.H. Lawrence',0),(@l+56,'Bukowski',0),
(@l+57,'Ẩn dật, không xuất bản khi còn sống',1),(@l+57,'Ở Paris lưu vong',0),(@l+57,'Trong bệnh viện tâm thần',0),(@l+57,'Trong tù',0),
(@l+58,'Không xác định rõ',1),(@l+58,'Naoki Maeda',0),(@l+58,'Ryū Murakami',0),(@l+58,'Haruki Murakami',0),
(@l+59,'Jean-Paul Sartre và Simone de Beauvoir',1),(@l+59,'Albert Camus',0),(@l+59,'Simone de Beauvoir',0),(@l+59,'Chỉ Sartre',0),
(@l+60,'Haruki Murakami',1),(@l+60,'Yasunari Kawabata',0),(@l+60,'Kenzaburō Ōe',0),(@l+60,'Yukio Mishima',0),
(@l+61,'Thơ trữ tình cách mạng',1),(@l+61,'Thơ lãng mạn',0),(@l+61,'Thơ tự do',0),(@l+61,'Thơ Đường luật',0),
(@l+62,'Hàn Mặc Tử',1),(@l+62,'Xuân Diệu',0),(@l+62,'Chế Lan Viên',0),(@l+62,'Nguyễn Bính',0),
(@l+63,'Nam Cao',1),(@l+63,'Ngô Tất Tố',0),(@l+63,'Nguyên Hồng',0),(@l+63,'Vũ Trọng Phụng',0),
(@l+64,'Tiểu thuyết ngắn (truyện vừa)',1),(@l+64,'Tiểu thuyết dài',0),(@l+64,'Truyện ngắn',0),(@l+64,'Kịch bản',0),
(@l+65,'Nicolai Gogol',1),(@l+65,'Dostoevsky',0),(@l+65,'Tolstoy',0),(@l+65,'Turgenev',0),
(@l+66,'Juan Rulfo (Mexico)',1),(@l+66,'García Márquez',0),(@l+66,'Borges',0),(@l+66,'Fuentes',0),
(@l+67,'Leaves of Grass',1),(@l+67,'Song of Myself',0),(@l+67,'Drum-Taps',0),(@l+67,'Democratic Vistas',0),
(@l+68,'Mỹ',1),(@l+68,'Anh',0),(@l+68,'Pháp',0),(@l+68,'Canada',0),
(@l+69,'Truyện ngắn',1),(@l+69,'Tiểu thuyết',0),(@l+69,'Kịch',0),(@l+69,'Thơ',0),
(@l+70,'Roland Barthes',1),(@l+70,'Michel Foucault',0),(@l+70,'Jacques Derrida',0),(@l+70,'Jacques Lacan',0),
(@l+71,'Jacques Derrida',1),(@l+71,'Roland Barthes',0),(@l+71,'Michel Foucault',0),(@l+71,'Paul de Man',0),
(@l+72,'Chủ nghĩa Tượng trưng (Symbolism)',1),(@l+72,'Lãng mạn',0),(@l+72,'Siêu thực',0),(@l+72,'Hiện đại',0),
(@l+73,'Julia Kristeva',1),(@l+73,'Roland Barthes',0),(@l+73,'Mikhail Bakhtin',0),(@l+73,'Genette',0),
(@l+74,'Marcel Proust, 7 tập',1),(@l+74,'James Joyce, 3 tập',0),(@l+74,'Tolstoy, 4 tập',0),(@l+74,'Dostoevsky, 5 tập',0),
(@l+75,'Nguyễn Dữ',1),(@l+75,'Nguyễn Du',0),(@l+75,'Lê Thánh Tông',0),(@l+75,'Ngô Sĩ Liên',0),
(@l+76,'Viết theo ràng buộc toán học/ngôn ngữ tự đặt ra',1),(@l+76,'Thơ siêu thực',0),(@l+76,'Tiểu thuyết lịch sử',0),(@l+76,'Văn học thiếu nhi',0),
(@l+77,'William Faulkner',1),(@l+77,'Hemingway',0),(@l+77,'Fitzgerald',0),(@l+77,'John Dos Passos',0),
(@l+78,'Holocaust / Shoah',1),(@l+78,'Chiến tranh Thế giới I',0),(@l+78,'Cách mạng Pháp',0),(@l+78,'Nội chiến',0),
(@l+79,'Alain Robbe-Grillet',1),(@l+79,'Albert Camus',0),(@l+79,'Jean-Paul Sartre',0),(@l+79,'Simone de Beauvoir',0),
(@l+80,'Viktor Shklovsky',1),(@l+80,'Roman Jakobson',0),(@l+80,'Boris Eichenbaum',0),(@l+80,'Vladimir Propp',0),
(@l+81,'William Faulkner',1),(@l+81,'Hemingway',0),(@l+81,'Fitzgerald',0),(@l+81,'Dos Passos',0),
(@l+82,'Viết bằng nhiều ngôn ngữ hư cấu đan xen, phi tuyến tính',1),(@l+82,'Văn xuôi thơ',0),(@l+82,'Chỉ tiếng Anh cổ',0),(@l+82,'Văn bản ngắn rời rạc',0),
(@l+83,'Sự đa thanh/giọng trong diễn ngôn văn hóa',1),(@l+83,'Phân tích nhân vật',0),(@l+83,'Nghiên cứu thần thoại',0),(@l+83,'Cấu trúc câu',0),
(@l+84,'Sprung rhythm (nhịp bật)',1),(@l+84,'Free verse',0),(@l+84,'Sonnet',0),(@l+84,'Villanelle',0),
(@l+85,'Đa giọng/nhiều ngôn ngữ xã hội cùng tồn tại trong văn bản',1),(@l+85,'Hai giọng kể',0),(@l+85,'Ngôn ngữ dân tộc',0),(@l+85,'Giọng trữ tình',0),
(@l+86,'Postmodern fiction / Hậu hiện đại',1),(@l+86,'Hiện đại',0),(@l+86,'Hiện thực huyền ảo',0),(@l+86,'Chủ nghĩa siêu thực',0),
(@l+87,'Hồ Xuân Hương (thơ Nôm)',1),(@l+87,'Nguyễn Du',0),(@l+87,'Đoàn Thị Điểm',0),(@l+87,'Bà Huyện Thanh Quan',0),
(@l+88,'Mahabharata (Ấn Độ, ~1,8 triệu chữ)',1),(@l+88,'War and Peace',0),(@l+88,'Clarissa',0),(@l+88,'Les Misérables',0);

-- ============================================================
-- BƯỚC 9: View tổng hợp để game linhvuc_id=8 có thể dùng
-- ============================================================
CREATE OR REPLACE VIEW `v_tonghop_cauhoi` AS
SELECT c.*, l.tenlinhvuc
FROM cauhoi c
JOIN linhvuc l ON c.linhvuc_id = l.id
WHERE c.linhvuc_id IN (1,2,3,4,5,6,7);

COMMIT;
