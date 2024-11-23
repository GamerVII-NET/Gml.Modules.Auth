const express = require('express');
const mysql = require('mysql');
const bodyParser = require('body-parser');
const bcrypt = require('bcrypt'); 

// Создание подключения к базе данных
const connection = mysql.createConnection({
  host: 'localhost', // Адрес базы данных MySQL
  user: 'ваш_пользователь',  // Пользователь БД
  password: 'ваш_пароль',  // Пароль пользователя БД
  database: 'ваша_база_данных'   // База данных
});

connection.connect((err) => {
  if (err) {
    console.error('Ошибка подключения к базе данных:', err);
    return;
  }
  console.log('Подключение к базе данных успешно установлено.');
});

const app = express();
app.use(bodyParser.json());

app.post('/auth', (req, res) => {
  const { username, password } = req.body;

  if (!username || !password) {
    return res.status(400).json({ Message: 'Логин и пароль обязательны' });
  }

  const query = 'SELECT uuid, login, password FROM users WHERE LOWER(login) = LOWER(?)';
  connection.query(query, [username], async (error, results) => {
    if (error) {
      console.error('Ошибка выполнения запроса к базе данных:', error);
      return res.status(500).json({ Message: 'Ошибка на сервере' });
    }

    if (results.length > 0) {
      

      const user = results[0];

      const dbPassword = user.password.startsWith('$2y$')
        ? user.password.replace('$2y$', '$2a$')
        : user.password;

      try {
        const match = await bcrypt.compare(password.trim(), dbPassword);

        if (match) {
          return res.json({
            Login: user.login,
            UserUuid: user.uuid,
            Message: 'Успешная авторизация'
          });
        } else {
          return res.status(401).json({ Message: 'Неверный логин или пароль' });
        }
      } catch (bcryptError) {
        console.error('Ошибка при сравнении пароля с хэшем:', bcryptError);
        return res.status(500).json({ Message: 'Ошибка на сервере' });
      }
    } else {
      return res.status(404).json({ Message: 'Пользователь не найден' });
    }
  });
});

const PORT = 3000;
app.listen(PORT, () => {
  console.log(`Сервер запущен на порту ${PORT}`);
});
