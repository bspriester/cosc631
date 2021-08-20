<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Now Or Later Reminders</title>
    <style type="text/css">
                html { color: #ddd; font-family: sans-serif; font-size: 16px; background-color: #000000; text-align: center; }
                header { text-align: center;}
                body {}
		footer {
		position: absolute;
		bottom:0;
		width: 99.5%;
		height:60px;
		}
		img {
		margin-left: auto;
		margin-right: auto;
		display: none;
		width: 70%
		}
                .main_body {margin: 30px;}
                ul {
                padding-left: 28px;
                list-style-position: outside;
                }
                .navbar {
                list-style-type: none;
                margin: 0;
                padding: 0;
                overflow: hidden;
                background-color: #333;
		font-size: 18px;
                }
                .navbar_buttons {
                display: block;
                color: #ddd;
                text-align: center;
                padding: 14px 16px;
                text-decoration: none;
                float: right;
                }
                .navbar_links {
                display: block;
                color: #ddd;
                text-align: center;
                padding: 14px 16px;
                text-decoration: none;
                float: left;
                }
                .navbar_links:hover {
                background-color: #111;
		}
                .light-mode {
                background-color: white;
                color: black;
		background-image: url(https://i.imgur.com/3uiFRXH.jpg);
                }
		.light-mode-navbar {
                background-color: gray;
                color: black;
                }
        </style>
</head>
<body>
    <!-- API stuff -->
    <form action="submit_reminder.php" method="post">
        Enter IP Address for Time Zone: <input type="text" name="ipAddress" placeholder="X.X.X.X">
        <input type="submit">
    </form>
    <?php
    //Get visitor IP, taking into account proxy possibility.
        function getIp() {
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            return $ip;
        }
        //Commenting out the below for testing.
        //$getIp = getIp();
        //$getIp = "76.231.70.33";
        //$getIp = "8.8.8.8";
        $getIp = $_POST['ipAddress'];
        $apiWithIp = "http://ip-api.com/json/" . $getIp;
        $getInfo = file_get_contents($apiWithIp);
        $decode = json_decode($getInfo);
        echo "Your timezone is: " . "$decode->timezone";
        //This works -> //echo $decode->timezone;
        //$ip = getIPAddress();
    ?>
    <br><br>
    <!-- Reminder set message -->
    <?php
        $reminderSetData = $_POST["reminder"];
        $reminderSetDate = $_POST["time"];
        $reminderSetMessage = "Reminder of $reminderSetData set for time $reminderSetDate";
        if ($reminderSetData == ""){
            $reminderSetMessage = "Submit new reminder below.";
        }
        echo $reminderSetMessage; 
    ?>
    <br>
    <!-- Set reminder form --> 
    <div class="main_page">
        <form action="submit_reminder.php" method="post">
            Reminder: <input type="text" name="reminder"><br>
            Time: <input type="text" name="time" placeholder="YYYY-MM-DD hh:mm:ss"><br>
            <input type="submit">
        </form>
    </div>
    <!-- MySQL setup -->
    <?php
        //Mysql connections:
        $con = mysqli_connect('localhost:3306', 'root', 'Bsz94290');
        //Check for connection error:
        if (!$con) {
            echo "Error: Unable to connect to MySQL." . PHP_EOL;
            echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
            echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
            exit;
        }
        //Create reminders_database database if it doesn't exist yet:
        $sql = "CREATE DATABASE IF NOT EXISTS reminders_database";
        $con->query($sql);
        //Select the reminders_database database:
        $sql = "use reminders_database";
        $con->query($sql);
        //Create the reminders_table if it doesn't exist:
        $sql = "CREATE TABLE IF NOT EXISTS reminders_table (ReminderId int, ReminderData text, ReminderDate datetime);";
        $con->query($sql);
        //Put the reminder data and time info from the reminder_app.php page into variables:
        $id = random_int(0, 1000000000);
        $reminder = $_POST["reminder"];
        $time = $_POST["time"];
        //Create new row in reminders_table:
        $sql = "INSERT INTO reminders_table(ReminderId, ReminderData, ReminderDate) VALUES (\"$id\", \"$reminder\", \"$time\");";
        $con->query($sql);
        //Update row in reminders_table:
        $updateId = $_POST["editId"];
        $updateData = $_POST["editData"];
        $updateDate = $_POST["editDate"];
        $sql = "UPDATE reminders_table SET ReminderData = \"$updateData\", ReminderDate = \"$updateDate\" WHERE ReminderId = $updateId;";
        $con->query($sql);
        //Delete row in reminders_table:
        $updateIdDelete = $_POST["deleteEditId"];
        $sql = "DELETE FROM reminders_table WHERE ReminderId = $updateIdDelete;";
        $con->query($sql);
    ?>
    <!-- Get reminders: -->
    <?php 
        $getTime = date("Y-m-d G:i:s"); 
        $dueFound = 0;
    ?>
    <table width="100%"  border="1">
        <tr>
            <th>Time</th>
            <th>Reminder</th>
            <th>Submit time or reminder changes</th>
            <th>Delete reminder</th>
        </tr>
        <?php
        $sql = "SELECT * FROM reminders_table WHERE ReminderDate < CURRENT_DATE ORDER BY ReminderDate DESC;";
        $query=mysqli_query($con, $sql);
        $cnt=1;
        //Commenting out the below since I believe it was for testing:
        //$fetchedInfo = mysqli_fetch_array($query);
        //echo $fetchedInfo['ReminderDate'];
        $previousDate = $null;
        while($row=mysqli_fetch_array($query))
        {
            $currentDate = $row['ReminderDate'];
            if(strncmp($currentDate, $previousDate, 10) != 0) {
        ?>
                <tr>
                    <td><b><?php echo "DAY " . substr($currentDate, 0, 10);?></b></td>
                    <td><?php echo "-";?></td>
                    <td><?php echo "-";?></td>
                    <td><?php echo "-";?></td>
                </tr>
                <form action="submit_reminder.php" method="post">
                    <tr>
                        <td><input type="text" name="editDate" value="<?php echo $row['ReminderDate']; ?>"></td>
                        <td><input type="text" name="editData" value="<?php echo $row['ReminderData']; ?>"></td>
                        <td>Submit changes <input type="submit">
                        <input type="hidden" name="editId" value="<?php echo $row['ReminderId']; ?>"></td>
                </form>
                <form action="submit_reminder.php" method="post">
                        <td>Delete <input type="submit">
                        <input type="hidden" name="deleteEditId" value="<?php echo $row['ReminderId']; ?>">
                        </td> 
                    </tr>
                </form>
                <?php
            }
            else { 
            ?>
                <form action="submit_reminder.php" method="post">
                    <tr>
                        <td><input type="text" name="editDate" value="<?php echo $row['ReminderDate']; ?>"></td>
                        <td><input type="text" name="editData" value="<?php echo $row['ReminderData']; ?>"></td>
                        <td>Submit changes <input type="submit">
                        <input type="hidden" name="editId" value="<?php echo $row['ReminderId']; ?>"></td>
                </form>
                <form action="submit_reminder.php" method="post">
                        <td>Delete <input type="submit">
                        <input type="hidden" name="deleteEditId" value="<?php echo $row['ReminderId']; ?>">
                        </td> 
                    </tr>
                </form>
        <?php
            }
            $cnt=$cnt+1;
            $previousDate = $row['ReminderDate'];
        } 
        ?>
    </table>
    <br><p><b>^ REMINDERS DUE ^</b></p><br>
    <table width="100%"  border="1">
        <tr>
            <th>Time</th>
            <th>Reminder</th>
            <th>Submit time or reminder changes</th>
            <th>Delete reminders</th>
        </tr>
        <?php
        $sql = "SELECT * FROM reminders_table WHERE ReminderDate > CURRENT_DATE ORDER BY ReminderDate DESC;";
        $query=mysqli_query($con, $sql);
        $cnt=1;
        //Commenting out the below since I believe it was for testing:
        //$fetchedInfo = mysqli_fetch_array($query);
        //echo $fetchedInfo['ReminderDate'];
        $previousDate = $null;
        while($row=mysqli_fetch_array($query))
        {
            $currentDate = $row['ReminderDate'];
            if(strncmp($currentDate, $previousDate, 10) != 0) {
        ?>
                <tr>
                    <td><b><?php echo "DAY " . substr($currentDate, 0, 10);?></b></td>
                    <td><?php echo "-";?></td>
                    <td><?php echo "-";?></td>
                    <td><?php echo "-";?></td>
                </tr>
                <form action="submit_reminder.php" method="post">
                    <tr>
                        <td><input type="text" name="editDate" value="<?php echo $row['ReminderDate']; ?>"></td>
                        <td><input type="text" name="editData" value="<?php echo $row['ReminderData']; ?>"></td>
                        <td>Submit changes <input type="submit">
                        <input type="hidden" name="editId" value="<?php echo $row['ReminderId']; ?>"></td>
                </form>
                <form action="submit_reminder.php" method="post">
                        <td>Delete <input type="submit">
                        <input type="hidden" name="deleteEditId" value="<?php echo $row['ReminderId']; ?>">
                        </td> 
                    </tr>
                </form>
                <?php
            }
            else { 
            ?>
                <form action="submit_reminder.php" method="post">
                    <tr>
                        <td><input type="text" name="editDate" value="<?php echo $row['ReminderDate']; ?>"></td>
                        <td><input type="text" name="editData" value="<?php echo $row['ReminderData']; ?>"></td>
                        <td>Submit changes <input type="submit">
                        <input type="hidden" name="editId" value="<?php echo $row['ReminderId']; ?>"></td>
                </form>
                <form action="submit_reminder.php" method="post">
                        <td>Delete <input type="submit">
                        <input type="hidden" name="deleteEditId" value="<?php echo $row['ReminderId']; ?>">
                        </td> 
                    </tr>
                </form>
        <?php
            }
            $cnt=$cnt+1;
            $previousDate = $row['ReminderDate'];
        } 
        ?>
    </table>
</body>
</html>