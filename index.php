<?php
header('Content-Type: text/html; charset=UTF-8');

// Установка времени жизни сессии (1 час)
session_set_cookie_params(3600);
session_start();


// Функция для получения значения поля.  Сначала проверяет сессию, потом куки.
function getFieldValue($fieldName) {
    // Сначала проверяем сессию
    if (isset($_SESSION['oldValues'][$fieldName])) {
        return htmlspecialchars($_SESSION['oldValues'][$fieldName]);
    }
    
    // Если в сессии нет, проверяем куки
    if (isset($_COOKIE['form_data'])) {
        $formData = json_decode($_COOKIE['form_data'], true);
        if (isset($formData[$fieldName])) {
            return htmlspecialchars($formData[$fieldName]);
        }
    }
    
    return ''; // Если нигде нет, возвращаем пустую строку
}

function getCheckboxValues($fieldName) {
  // Сначала проверяем сессию
  //PRINT_R($_SESSION['oldValues']);
if (isset($_SESSION['oldValues'][$fieldName]) && is_array($_SESSION['oldValues'][$fieldName])) {
      return $_SESSION['oldValues'][$fieldName];
  }

  //PRINT_R($_COOKIE['form_data']);

  // Если в сессии нет, проверяем куки
  if (isset($_COOKIE['form_data'])) {
      $formData = json_decode($_COOKIE['form_data'], true);
      if (isset($formData[$fieldName]) && is_array($formData[$fieldName])) {
          return $formData[$fieldName];
      }
  }

  return []; // Если нигде нет, возвращаем пустой массив
}


// Обработка POST-запроса (отправка формы)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Массивы для хранения ошибок
    $errors = false;
    $formErrors = [];
    $fieldErrors = [];

    //print_r($_SESSION);
    //die();
    
// Валидация ФИО 
if (empty($_POST['fio'])) {
    $fieldErrors['fio'] = 'Поле ФИО обязательно для заполнения.';
    $errors = true;
} else {
    // Удаляем все разрешенные символы и проверяем, осталось ли что-то
    $cleaned = preg_replace('/[а-яА-ЯёЁa-zA-Z\s-]/u', '', $_POST['fio']);
    if (!empty($cleaned)) {
        $fieldErrors['fio'] = 'ФИО может содержать только буквы, пробелы и дефисы.';
        $errors = true;
    } elseif (strlen($_POST['fio']) > 150) {
        $fieldErrors['fio'] = 'ФИО не должно превышать 150 символов.';
        $errors = true;
    }
}

// Валидация телефона (теперь точно не пропустит буквы)
if (empty($_POST['tel'])) {
    $fieldErrors['tel'] = 'Поле телефона обязательно для заполнения.';
    $errors = true;
} else {
    // Удаляем все разрешенные символы
    $cleaned = preg_replace('/[\d\s\-\+\(\)]/', '', $_POST['tel']);
    if (!empty($cleaned)) {
        $fieldErrors['tel'] = 'Телефон должен содержать только цифры, пробелы, +, - или скобки.';
        $errors = true;
    } else {
        // Проверяем количество цифр (от 6 до 20)
        $digitCount = preg_match_all('/\d/', $_POST['tel']);
        if ($digitCount < 6 || $digitCount > 20) {
            $fieldErrors['tel'] = 'Телефон должен содержать от 6 до 20 цифр.';
            $errors = true;
        }
    }
}

    
    // Валидация email
    if (empty($_POST['email'])) {
        $fieldErrors['email'] = 'Поле email обязательно для заполнения.';
        $errors = true;
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Пожалуйста, введите корректный email.';
        $errors = true;
    }
    
// Валидация даты рождения 
if (empty($_POST['date'])) {
    $fieldErrors['date'] = 'Поле даты рождения обязательно для заполнения.';
    $errors = true;
} else {
    // Проверяем формат
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'])) {
        $fieldErrors['date'] = 'Пожалуйста, введите дату в формате ГГГГ-ММ-ДД.';
        $errors = true;
    } else {
        // Разбираем дату на компоненты
        list($year, $month, $day) = explode('-', $_POST['date']);
        
        // Проверяем валидность даты
        if (!checkdate($month, $day, $year)) {
            $fieldErrors['date'] = 'Некорректная дата.';
            $errors = true;
        } else {
            $birthDate = new DateTime($_POST['date']);
            $today = new DateTime();
            $minDate = new DateTime('1900-01-01');
            
            if ($birthDate > $today) {
                $fieldErrors['date'] = 'Дата рождения не может быть в будущем.';
                $errors = true;
            } elseif ($birthDate < $minDate) {
                $fieldErrors['date'] = 'Дата рождения не может быть раньше 1900 года.';
                $errors = true;
            } elseif ($today->diff($birthDate)->y < 18) {
                $fieldErrors['date'] = 'Вы должны быть старше 18 лет.';
                $errors = true;
            }
        }
    }
}
    
    // Валидация пола
    if (empty($_POST['gender']) || !in_array($_POST['gender'], ['Мужской', 'Женский'])) {
        $fieldErrors['gender'] = 'Пожалуйста, выберите пол.';
        $errors = true;
    }
    
    // Валидация языков программирования
    if (empty($_POST['plang']) || !is_array($_POST['plang'])) {
        $fieldErrors['plang'] = 'Пожалуйста, выберите хотя бы один язык программирования.';
        $errors = true;
    } else {
        //$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Haskell', 'Clojure', 'Prolog', 'Scala'];
        foreach ($_POST['plang'] as $lang) {
            if (!($lang > 0 && $lang <= 12)) {
                $fieldErrors['plang'] = 'Выбран недопустимый язык программирования.';
                $errors = true;
                break;
            }
        }
    }
    
    // Валидация биографии
    if (empty($_POST['bio'])) {
        $fieldErrors['bio'] = 'Поле биографии обязательно для заполнения.';
        $errors = true;
    } elseif (strlen($_POST['bio']) > 500) {
        $fieldErrors['bio'] = 'Биография не должна превышать 500 символов.';
        $errors = true;
    }
    
    // Валидация чекбокса
    if (empty($_POST['check'])) {
        $fieldErrors['check'] = 'Необходимо подтвердить согласие на обработку персональных данных.';
        $errors = true;
    }
    
    // Если есть ошибки, сохраняем их в сессию и возвращаем на форму
    if ($errors) {
        $_SESSION['formErrors'] = $fieldErrors;//['Пожалуйста, исправьте указанные ошибки.'];
        $_SESSION['fieldErrors'] = $fieldErrors;
        $_SESSION['oldValues'] = $_POST;
        
        header('Location: index.php');
        exit();
    }
    
    // Если ошибок нет, сохраняем данные в БД и куки
    
    $user = 'u69186'; // Заменить на ваш логин uXXXXX
  $pass = '8849997'; // Заменить на пароль
  $db = new PDO('mysql:host=localhost;dbname=u69186', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); // Заменить test на имя БД, совпадает с логином uXXXXX
  
  // Подготовленный запрос. Не именованные метки.
  try {
    $stmt = $db->prepare("INSERT INTO application ( fio, phone, email, birthdate, gender, biography, contract_accepted ) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['fio'], $_POST['tel'], $_POST['email'], $_POST['date'], $_POST['gender'], $_POST['bio'], $_POST['check'] ]);

    $new_id = $db->lastInsertId();

    foreach ($_POST['plang'] as $language) {
      $stmt = $db->prepare("INSERT INTO application_programming_language ( application_id, programming_language_id ) VALUES (?, ?)");
    $stmt->execute([$new_id, $language]);
    }
  
        
        // Сохраняем данные в куки на 1 год
        $formData = [
            'fio' => $_POST['fio'],
            'tel' => $_POST['tel'],
            'email' => $_POST['email'],
            'date' => $_POST['date'],
            'gender' => $_POST['gender'],
            'bio' => $_POST['bio'],
            'plang' => $_POST['plang']
        ];
        
        setcookie('form_data', json_encode($formData), time() + 3600 * 24 * 365, '/');
        
        // Перенаправляем с флагом успешного сохранения
        header('Location: index.php?save=1');
        exit();
    } catch (PDOException $e) {
        $_SESSION['formErrors'] = ['Ошибка при сохранении данных: ' . $e->getMessage()];
        $_SESSION['oldValues'] = $_POST;
        header('Location: index.php');
        exit();
    }
}

// Получение старых значений (сначала из сессии, потом из куки)
$fioValue = getFieldValue('fio');
$telValue = getFieldValue('tel');
$emailValue = getFieldValue('email');
$dateValue = getFieldValue('date');
$genderValue = getFieldValue('gender');
$bioValue = getFieldValue('bio');
$plangValues = getCheckboxValues('plang');

$fieldErrors = (!empty($_SESSION['fieldErrors']))?$_SESSION['fieldErrors']:[];
$formErrors = (!empty($_SESSION['formErrors']))?$_SESSION['formErrors']:[];

//print_r($_SESSION);


// Очистка сообщений об ошибках после их отображения
if (!empty($_SESSION['formErrors'])) {
    unset($_SESSION['formErrors']);
}
if (!empty($_SESSION['fieldErrors'])) {
    unset($_SESSION['fieldErrors']);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Опрос студентов</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Опрос</h2>
        
        <?php if (!empty($formErrors)): ?>
        <div class="form-messages">
            <p>Пожалуйста, исправьте следующие ошибки:</p>
            <ul>
                <?php foreach ($formErrors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($_GET['save'])): ?>
            <div style="color: green; margin-bottom: 10px;">Данные успешно сохранены!</div>
        <?php endif; ?>
        
        <form id="registrationForm" action="index.php" method="POST">
            <div class="form-row">
                <label for="fio">ФИО:</label>
                <input type="text" name="fio" class="form-control <?php echo !empty($fieldErrors['fio']) ? 'error-field' : ''; ?>" 
                       id="fio" placeholder="Кособуцкая Мария Сергеевна" required
                       value="<?php echo getFieldValue('fio'); ?>">
                <?php if (!empty($fieldErrors['fio'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['fio']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="tel">Номер телефона:</label>
                <input type="tel" name="tel" class="form-control <?php echo !empty($fieldErrors['tel']) ? 'error-field' : ''; ?>" 
                       id="tel" placeholder="8-800-555-35-35" required
                       value="<?php echo getFieldValue('tel'); ?>">
                <?php if (!empty($fieldErrors['tel'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['tel']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="email">Email:</label>
                <input type="email" name="email" class="form-control <?php echo !empty($fieldErrors['email']) ? 'error-field' : ''; ?>" 
                       id="email" placeholder="example@mail.ru" required
                       value="<?php echo getFieldValue('email'); ?>">
                <?php if (!empty($fieldErrors['email'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="date">Дата рождения:</label>
                <input type="date" name="date" class="form-control <?php echo !empty($fieldErrors['date']) ? 'error-field' : ''; ?>" 
                       id="date" required
                       value="<?php echo getFieldValue('date'); ?>">
                <?php if (!empty($fieldErrors['date'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['date']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label>Пол:</label>
                <div class="gender-container">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="radio-male" value="Мужской" required
                            <?php echo (getFieldValue('gender') == 'Мужской') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="radio-male">Мужской</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="radio-female" value="Женский" required
                            <?php echo (getFieldValue('gender') == 'Женский') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="radio-female">Женский</label>
                    </div>
                </div>
                <?php if (!empty($fieldErrors['gender'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['gender']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="plang">Любимый язык программирования:</label>
                <select class="form-control <?php echo !empty($fieldErrors['plang']) ? 'error-field' : ''; ?>" 
                        name="plang[]" id="plang" multiple required>
                    <?php
                    $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python','Java','Haskell', 'Clojure', 'Prolog', 'Scala','Go'];
                    $plangValues = getCheckboxValues('plang');
                    foreach ($languages as $key=>$lang) {
                        $selected = (in_array($key+1, $plangValues)) ? 'selected' : '';
                        echo "<option value=\"".($key+1)."\" $selected>$lang</option>";
                    }
                    ?>
                </select>
                <?php if (!empty($fieldErrors['plang'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['plang']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <label for="bio">Биография:</label><br/>
                <textarea class="form-control <?php echo !empty($fieldErrors['bio']) ? 'error-field' : ''; ?>" 
                          name="bio" id="bio" rows="3" placeholder="Расскажите что-нибудь о себе" required><?php 
                    echo getFieldValue('bio'); 
                ?></textarea>
                <?php if (!empty($fieldErrors['bio'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['bio']); ?></div>
                <?php endif; ?>
            </div>
            
            <div>
                <input type="checkbox" class="form-check-input" name="check" id="check" value="1"
                    <?php echo (!empty($plangValues)) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="check">Согласие на обработку персональных данных</label>
                <?php if (!empty($fieldErrors['check'])): ?>
                <div class="error"><?php echo htmlspecialchars($fieldErrors['check']); ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>
