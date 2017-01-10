<?php
namespace App\Lib;

/**
 * Класс-одиночка с функционалом отображения и переключения git веток разработки для предрелизной ветки CRM
 *
 * @package App\Lib
 */
class Git
{
	const TEST_SERVER_NAME = 'crm-test.artskills.ru'; // домен, для которого доступен функционал работы с ветками

	const BRANCH_NAME_MASTER = 'master';
	const BRANCH_NAME_HEAD = 'HEAD';

	const BRANCH_TYPE_REMOTE = 'remote';
	const BRANCH_TYPE_LOCAL = 'local';
	const BRANCH_TYPE_ALL = 'all';


	/**
	 * Команда запуска git на тесте
	 *
	 * @var string
	 */
	const GIT_COMMAND_TEST = '/var/www/git.sh';

	/**
	 * Команда запуска git на локальных тачках
	 *
	 * @var string
	 */
	const GIT_COMMAND_LOCAL = 'git';

	/**
	 * Текущая ветка
	 *
	 * @var string
	 */
	private $_currentBranch = '';

	/**
	 * Команда запуска git
	 *
	 * @var string
	 */
	private $_gitCommand = '';

	/**
	 * Папка репозитория
	 *
	 * @var string
	 */
	private $_repositoryDir = '';

	/**
	 * Выбираем, какой командой обращаться к гиту; вытаскиваем текущую ветку
	 *
	 * @param string $deployKey
	 * @param string $repositoryDir
	 * @throws \Exception
	 */
	public function __construct($deployKey, $repositoryDir) {
		if (!file_exists($deployKey)) {
			throw new \Exception('File "' . $deployKey . '" not exists!');
		}
		if (!is_dir($repositoryDir)) {
			throw new \Exception('Directory "' . $repositoryDir . '" not exists!');
		}

		$this->_gitCommand = self::GIT_COMMAND_TEST . ' -i ' . $deployKey;
		$this->_repositoryDir = $repositoryDir;

		if (!empty($this->_gitCommand)) {
			$this->_currentBranch = $this->_getCurrentBranch();
		}
	}

	/**
	 * Возвращаем текущую активную git ветку
	 *
	 * @return string
	 */
	public function getCurrentBranchName() {
		return $this->_currentBranch;
	}


	/**
	 * Исполняет команду, находясь в мастере и переключает ветку обратно. Возвращает вывод команды
	 *
	 * @param string $command
	 * @return array
	 */
	private function _execFromMaster($command) {
		$currentBranch = $this->_currentBranch;
		$this->_checkout(self::BRANCH_NAME_MASTER);
		$this->pullCurrentBranch();
		$output = $this->_execute($command);
		$this->_checkout($currentBranch);
		return $output;
	}

	/**
	 * Смена активной ветки
	 *
	 * @param string $name
	 * @param string $gitOutput
	 * @return bool
	 */
	public function checkout($name, &$gitOutput = null) {
		$gitOutput = '';
		if ($this->_currentBranch == $name) {
			return true;
		}
		if (empty($this->_currentBranch) || !in_array($name, $this->getBranchList(self::BRANCH_TYPE_ALL))) {
			return false;
		}
		return $this->_checkout($name, $gitOutput);
	}

	/**
	 * Для внутреннего пользования, без проверок
	 *
	 * @param string $name
	 * @param string $gitOutput
	 * @return bool
	 */
	private function _checkout($name, &$gitOutput = null) {
		if ($this->_currentBranch == $name) {
			return true;
		}
		$command = $this->_gitCommand . ' checkout ' . $name;
		$resultArr = $this->_execute($command);
		if ($gitOutput !== null) {
			$gitOutput = implode("\n", $resultArr);
		}

		$newBranch = $this->_getCurrentBranch();
		if ($newBranch == $name) {
			$this->_currentBranch = $name;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Удаляет ветку
	 *
	 * @param string $name
	 * @param string $type локальная или удалённая
	 * @return bool
	 */
	public function deleteBranch($name, $type) {
		if (
			empty($this->_currentBranch)
			|| (($name == $this->_currentBranch) && ($type == self::BRANCH_TYPE_LOCAL))
			|| in_array($name, [self::BRANCH_NAME_HEAD, self::BRANCH_NAME_MASTER])
			|| empty($this->getMergedBranches($type)[$name])
		) {
			return false;
		}
		if ($type == self::BRANCH_TYPE_REMOTE) {
			$command = $this->_gitCommand . ' push origin --delete ' . $name;
		} else {
			$command = $this->_gitCommand . ' branch ' . $name . ' -d';
		}
		$this->_execFromMaster($command);
		return true;
	}


	/**
	 * Делаем git pull для активной ветки
	 *
	 * @param string $gitOutput
	 * @return bool
	 */
	public function pullCurrentBranch(&$gitOutput = null) {
		if (empty($this->_currentBranch)) {
			return false;
		}

		$cmd = $this->_gitCommand . ' pull';
		$result = $this->_execute($cmd);
		if ($gitOutput !== null) {
			$gitOutput = implode("\n", $result);
		}
		return true;
	}


	/**
	 * Обновляет список веток
	 *
	 * @return bool
	 */
	public function updateRefs() {
		if (empty($this->_currentBranch)) {
			return false;
		}
		$command = $this->_gitCommand . ' remote update --prune';
		$this->_execute($command);
		return true;
	}


	/**
	 * Смена ветки и pull
	 *
	 * @param string $branchName
	 * @return bool
	 */
	public function changeCurrentBranch($branchName) {
		if (empty($this->_currentBranch) || !in_array($branchName, $this->getBranchList(self::BRANCH_TYPE_REMOTE))) {
			return false;
		}

		return ($this->_checkout($branchName) && $this->pullCurrentBranch());
	}


	/**
	 * Выгружаем список доступных веток в git
	 *
	 * @param string $type локальная или удалённая
	 * @return array
	 */
	public function getBranchList($type) {
		if (empty($this->_currentBranch)) {
			return [];
		}
		$result = [];
		switch ($type) {
			case self::BRANCH_TYPE_REMOTE:
				$commandParam = ' -r';
				$branchPrefix = '(origin\/)';
				break;
			case self::BRANCH_TYPE_LOCAL:
				$commandParam = '';
				$branchPrefix = '(\s*)';
				break;
			case self::BRANCH_TYPE_ALL:
				$commandParam = ' -a';
				$branchPrefix = '(remotes\/origin\/)?';
				break;
			default:
				return [];
		}
		$branchList = $this->_execute($this->_gitCommand . ' branch' . $commandParam);
		$nameRegexp = '/' . $branchPrefix . '([0-9a-z\-\_\.A-Z]+)/i';
		foreach ($branchList as $branchName) {
			if (stristr($branchName, self::BRANCH_NAME_HEAD)) {
				continue;
			} elseif (preg_match($nameRegexp, $branchName, $matches)) {
				$result[] = $matches[2];
			}
		}
		return array_unique($result);
	}


	/**
	 * Возвращает список веток, смерженных с мастером, с датами последнего коммита
	 *
	 * @param string $type локальная или удалённая
	 * @return array
	 */
	public function getMergedBranches($type) {
		if (empty($this->_currentBranch)) {
			return [];
		}

		if ($type == self::BRANCH_TYPE_REMOTE) {
			$namePattern = 'refs/remotes/origin';
		} elseif ($type == self::BRANCH_TYPE_LOCAL) {
			$namePattern = 'refs/heads';
		} else {
			return [];
		}

		$command = $this->_gitCommand . ' for-each-ref --format="%(refname) %(authordate:short)" ' . $namePattern . ' --merged';
		$branchList = $this->_execFromMaster($command);

		$branchDates = [];
		foreach ($branchList as $branchData) {
			list($branchName, $lastCommitDate) = explode(' ', $branchData);
			$branchName = str_replace($namePattern . '/', '', $branchName);
			if (empty($branchName)) {
				continue;
			}
			$branchDates[$branchName] = $lastCommitDate;
		}
		unset($branchDates[self::BRANCH_NAME_MASTER], $branchDates[self::BRANCH_NAME_HEAD]);

		return $branchDates;
	}

	/**
	 * Выполняем команду
	 *
	 * @param string $command
	 * @return array
	 */
	private function _execute($command) {
		return System::execute($command, $this->_repositoryDir, false);
	}

	/**
	 * Определяем имя текущей ветки
	 *
	 * @return string
	 */
	private function _getCurrentBranch() {
		$result = $this->_execute($this->_gitCommand . ' rev-parse --abbrev-ref HEAD');
		if (!empty($result)) {
			return $result[0];
		} else {
			return '';
		}
	}
}
