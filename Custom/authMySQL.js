const express = require('express');
const mysql = require('mysql2');
const bodyParser = require('body-parser');

// Создание подключения к базе данных
const connection = mysql.createConnection({
  host: 'localhost', // Адрес базы данных MySQL
  user: 'ваш_пользователь',  // Пользователь БД
  password: 'ваш_пароль',  // Пароль пользователя БД
  database: 'ваша_база_данных'   // База данных
});

connection.connect();

const app = express();
app.use(bodyParser.json());

// Эндпоинт для авторизации
app.post('/auth', function (req, res) {
  const { Login, Password } = req.body;
  // Запрос к базе данных для проверки пользователя. !!!! Необходимо заменить на свою таблицу и колонку
  const query = 'SELECT uuid, login FROM users WHERE login = ? AND password = ?';
  connection.query(query, [Login, Password], function (error, results) {
    if (error) {
      return res.status(500).json({ Message: 'Ошибка на сервере' });
    }
    if (results.length > 0) {
      // Пользователь найден
      res.json({
        Login: results[0].login,
        UserUuid: results[0].uuid,
        Message: 'Успешная авторизация'
      });
    } else {
      // Проверка наличия пользователя с таким логином
      connection.query('SELECT login FROM users WHERE login = ?', [Login], function (error, results) {
        if (error) {
          return res.status(500).json({ Message: 'Ошибка на сервере' });
        }
        if (results.length > 0) {
          // Неверный пароль
          res.status(401).json({ Message: 'Неверный логин или пароль' });
        } else {
          // Пользователь не найден
          res.status(404).json({ Message: 'Пользователь не найден' });
        }
      });
    }
  });
});

const PORT = 3000;
app.listen(PORT, function () {
  console.log(`Server is running on port ${PORT}`);
});
