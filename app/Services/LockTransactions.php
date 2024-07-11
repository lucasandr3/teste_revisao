<?php

namespace App\Services;

use Exception;

class LockTransactions
{
    public const LOCK_FILE = 'transactions.lock';

    /**
     * @throws Exception
     */
    public function lock($timeout = 5)
    {
        $lockFile = base_path(self::LOCK_FILE);
        $fileOpened = fopen($lockFile, 'c');

        if ($fileOpened === false) {
            throw new \RuntimeException('Não possível criar o arquivo de lock');
        }

        $startTime = time();

        do {
            $canLock = flock($fileOpened, LOCK_EX | LOCK_NB, $wouldBlock);

            if ($canLock) {
                return $fileOpened;
            }

            if (!$wouldBlock) {
                fclose($fileOpened);
                throw new \RuntimeException('Erro ao iniciar lock');
            }

            usleep(100000);
        } while ((time() - $startTime) < $timeout);

        fclose($fileOpened);
        return false;
    }

    /**
     * @param $lock
     */
    public function unLock($lock): void
    {
        if ($lock !== false) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
