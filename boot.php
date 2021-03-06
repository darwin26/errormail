<?php
	if (!rex::isBackend()) {
		rex_extension::register('RESPONSE_SHUTDOWN', function (rex_extension_point $ep) {
			$logFile = rex_path::coreCache('system.log');
            $fileTime = filemtime($logFile);
            $sendTime = $this->getConfig('last_log_file_send_time', 0);


			if ($fileTime != $sendTime && $file = new rex_log_file($logFile)) {
				//Start - generate mailbody
					$mailBody = '';
					$mailBody .= '<table>';
					$mailBody .= '	<thead>';
					$mailBody .= '		<tr>';
					$mailBody .= '			<th>' . rex_i18n::msg('syslog_timestamp') . '</th>';
					$mailBody .= '			<th>' . rex_i18n::msg('syslog_type') . '</th>';
					$mailBody .= '			<th>' . rex_i18n::msg('syslog_message') . '</th>';
					$mailBody .= '			<th>' . rex_i18n::msg('syslog_file') . '</th>';
					$mailBody .= '			<th>' . rex_i18n::msg('syslog_line') . '</th>';
					$mailBody .= '		</tr>';
					$mailBody .= '	</thead>';
					$mailBody .= '	<tbody>';
					
					foreach (new LimitIterator($file, 0, 30) as $entry) {
						/* @var rex_log_entry $entry */
						$data = $entry->getData();
						
						$mailBody .= '		<tr>';
						$mailBody .= '			<td>' . $entry->getTimestamp('%d.%m.%Y %H:%M:%S') . '</td>';
						$mailBody .= '			<td>' . $data[0] . '</td>';
						$mailBody .= '			<td>' . $data[1] . '</td>';
						$mailBody .= '			<td>' . (isset($data[2]) ? $data[2] : '') . '</td>';
						$mailBody .= '			<td>' . (isset($data[3]) ? $data[3] : '') . '</td>';
						$mailBody .= '		</tr>';
					}
					
					$mailBody .= '	</tbody>';
					$mailBody .= '</table>';
				//End - generate mailbody
				
				//Start  send mail
					$mail = new rex_mailer();
					$mail->Subject = rex::getServerName().' | system.log';
					$mail->Body    = $mailBody;
					$mail->AltBody = strip_tags($mailBody);
					$mail->setFrom(rex::getErrorEmail(), 'REDAXO Errormail');
					$mail->addAddress(rex::getErrorEmail());
                    $this->setConfig('last_log_file_send_time', $fileTime);

					if ($mail->Send()) {
						// close logger, to free remaining file-handles to syslog
						rex_logger::close();
					}
				//End  send mail
			}
		});
	}
?>