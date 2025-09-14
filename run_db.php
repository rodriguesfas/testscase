<?php
/**
 * Sistema de teste para códigos de maratona de programação
 * Versão com banco de dados PostgreSQL
 * Substitui o run.php original, mas trabalha com dados do banco
 */

// Configuração do banco
$config = [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'codedojo',
    'user' => 'codedojo_user',
    'password' => 'codedojo_pass'
];

/**
 * Conecta ao banco PostgreSQL
 */
function connectDatabase($config) {
    try {
        $pdo = new PDO(
            "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", 
            $config['user'], 
            $config['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n");
    }
}

/**
 * Detecta a linguagem com base na extensão do arquivo
 */
function detectLanguage($filepath) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $languageMap = [
        'py' => 'python',
        'cpp' => 'cpp',
        'c' => 'c',
        'java' => 'java',
        'js' => 'javascript',
        'ts' => 'typescript',
        'go' => 'go',
        'rs' => 'rust',
        'php' => 'php'
    ];
    return $languageMap[$ext] ?? 'unknown';
}

/**
 * Compila (se necessário) e executa código em diferentes linguagens
 */
function compileAndExecute($filepath, $inputData, $language) {
    $startTime = microtime(true);
    
    try {
        switch ($language) {
            case 'python':
                $result = executePython($filepath, $inputData);
                break;
            case 'c':
            case 'cpp':
                $result = executeCCpp($filepath, $inputData, $language);
                break;
            case 'java':
                $result = executeJava($filepath, $inputData);
                break;
            case 'javascript':
                $result = executeJavaScript($filepath, $inputData);
                break;
            case 'go':
                $result = executeGo($filepath, $inputData);
                break;
            case 'rust':
                $result = executeRust($filepath, $inputData);
                break;
            case 'php':
                $result = executePHP($filepath, $inputData);
                break;
            default:
                $result = ["Linguagem '$language' não suportada", false];
        }
        
        $executionTime = (microtime(true) - $startTime) * 1000; // em ms
        return [$result[0], $result[1], $executionTime];
        
    } catch (Exception $e) {
        $executionTime = (microtime(true) - $startTime) * 1000;
        return ["Erro na execução: " . $e->getMessage(), false, $executionTime];
    }
}

// Funções de execução (mantendo as mesmas do run.php original)
function executePython($filepath, $inputData) {
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open("python3 \"$filepath\"", $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        fwrite($pipes[0], $inputData);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            return ["Erro de execução: $error", false];
        }
        
        return [$output, true];
    }
    
    return ["Falha ao executar processo Python", false];
}

function executeCCpp($filepath, $inputData, $language) {
    $tempDir = sys_get_temp_dir() . '/codedojo_' . uniqid();
    mkdir($tempDir, 0777, true);
    
    $executable = $tempDir . '/program';
    $compiler = ($language === 'c') ? 'gcc' : 'g++';
    
    try {
        $compileCmd = "$compiler -o \"$executable\" \"$filepath\" 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);
        
        if ($compileReturn !== 0) {
            return ["Erro de compilação: " . implode("\n", $compileOutput), false];
        }
        
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open("\"$executable\"", $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fwrite($pipes[0], $inputData);
            fclose($pipes[0]);
            
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnCode = proc_close($process);
            
            return [$output, $returnCode === 0];
        }
        
        return ["Falha ao executar processo", false];
        
    } finally {
        if (file_exists($executable)) {
            unlink($executable);
        }
        rmdir($tempDir);
    }
}

function executeJavaScript($filepath, $inputData) {
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open("node \"$filepath\"", $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        fwrite($pipes[0], $inputData);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);
        
        return [$output, $returnCode === 0];
    }
    
    return ["Falha ao executar processo JavaScript", false];
}

function executeJava($filepath, $inputData) {
    $tempDir = sys_get_temp_dir() . '/codedojo_' . uniqid();
    mkdir($tempDir, 0777, true);
    
    $filename = basename($filepath);
    $tempJava = $tempDir . '/' . $filename;
    copy($filepath, $tempJava);
    
    try {
        $compileCmd = "javac \"$tempJava\" 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);
        
        if ($compileReturn !== 0) {
            return ["Erro de compilação: " . implode("\n", $compileOutput), false];
        }
        
        $className = pathinfo($filename, PATHINFO_FILENAME);
        
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open("java -cp \"$tempDir\" $className", $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fwrite($pipes[0], $inputData);
            fclose($pipes[0]);
            
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnCode = proc_close($process);
            
            return [$output, $returnCode === 0];
        }
        
        return ["Falha ao executar processo Java", false];
        
    } finally {
        array_map('unlink', glob("$tempDir/*"));
        rmdir($tempDir);
    }
}

function executeGo($filepath, $inputData) {
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open("go run \"$filepath\"", $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        fwrite($pipes[0], $inputData);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);
        
        return [$output, $returnCode === 0];
    }
    
    return ["Falha ao executar processo Go", false];
}

function executeRust($filepath, $inputData) {
    $tempDir = sys_get_temp_dir() . '/codedojo_' . uniqid();
    mkdir($tempDir, 0777, true);
    
    $executable = $tempDir . '/program';
    
    try {
        $compileCmd = "rustc -o \"$executable\" \"$filepath\" 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);
        
        if ($compileReturn !== 0) {
            return ["Erro de compilação: " . implode("\n", $compileOutput), false];
        }
        
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open("\"$executable\"", $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fwrite($pipes[0], $inputData);
            fclose($pipes[0]);
            
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnCode = proc_close($process);
            
            return [$output, $returnCode === 0];
        }
        
        return ["Falha ao executar processo", false];
        
    } finally {
        if (file_exists($executable)) {
            unlink($executable);
        }
        rmdir($tempDir);
    }
}

function executePHP($filepath, $inputData) {
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];

    $process = proc_open("php \"$filepath\"", $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        fwrite($pipes[0], $inputData);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            return ["Erro de execução PHP: $error", false];
        }
        
        return [$output, true];
    }
    
    return ["Falha ao executar processo PHP", false];
}

/**
 * Submete código para um desafio específico
 */
function submitCode($pdo, $challengeName, $filepath, $author = 'Sistema') {
    $language = detectLanguage($filepath);
    $filename = basename($filepath);
    $sourceCode = file_get_contents($filepath);
    
    // Buscar ID do desafio
    $stmt = $pdo->prepare("SELECT id, title FROM challenges WHERE name = ?");
    $stmt->execute([$challengeName]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$challenge) {
        echo "❌ Desafio '$challengeName' não encontrado no banco\n";
        return false;
    }
    
    // Inserir submissão
    $stmt = $pdo->prepare("
        INSERT INTO submissions (challenge_id, filename, language, source_code, author) 
        VALUES (?, ?, ?, ?, ?) 
        RETURNING id
    ");
    
    $stmt->execute([
        $challenge['id'],
        $filename,
        $language,
        $sourceCode,
        $author
    ]);
    
    $submissionId = $stmt->fetchColumn();
    
    if (!$submissionId) {
        echo "❌ Erro ao submeter código\n";
        return false;
    }
    
    echo "✅ Código submetido com ID: $submissionId\n";
    return $submissionId;
}

/**
 * Executa todos os testes para uma submissão específica
 */
function runTests($pdo, $submissionId) {
    // Buscar dados da submissão
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as challenge_name, c.title 
        FROM submissions s 
        JOIN challenges c ON s.challenge_id = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$submissionId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        echo "❌ Submissão não encontrada\n";
        return false;
    }
    
    // Criar arquivo temporário com o código
    $tempFile = tempnam(sys_get_temp_dir(), 'codedojo_') . '.' . 
                ($submission['language'] === 'cpp' ? 'cpp' : 
                 ($submission['language'] === 'python' ? 'py' : 
                  ($submission['language'] === 'javascript' ? 'js' : $submission['language'])));
    
    file_put_contents($tempFile, $submission['source_code']);
    
    try {
        echo "{$submission['challenge_name']} - {$submission['title']} / (linguagem: {$submission['language']})\n";
        echo "│\n";

        $correctTotal = 0;
        $wrongTotal = 0;
        $totalExecutionTime = 0;
        
        // Buscar casos de teste agrupados por pasta
        $stmt = $pdo->prepare("
            SELECT folder_number, test_number, input_data, expected_output 
            FROM test_cases 
            WHERE challenge_id = ? 
            ORDER BY folder_number, test_number
        ");
        $stmt->execute([$submission['challenge_id']]);
        $testCases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($testCases)) {
            echo "❌ Nenhum caso de teste encontrado\n";
            return false;
        }
        
        // Criar execução
        $stmt = $pdo->prepare("
            INSERT INTO executions (submission_id, total_tests, status) 
            VALUES (?, ?, 'running') 
            RETURNING id
        ");
        $stmt->execute([$submissionId, count($testCases)]);
        $executionId = $stmt->fetchColumn();
        
        // Agrupar por pasta
        $testsByFolder = [];
        foreach ($testCases as $test) {
            $testsByFolder[$test['folder_number']][] = $test;
        }
        
        foreach ($testsByFolder as $folderNumber => $folderTests) {
            echo "│\n";
            echo "├── folder $folderNumber\n";

            $correctFolder = 0;
            $wrongFolder = 0;

            foreach ($folderTests as $test) {
                [$actualOutput, $success, $executionTime] = compileAndExecute(
                    $tempFile, 
                    $test['input_data'], 
                    $submission['language']
                );
                
                $totalExecutionTime += $executionTime;

                if (!$success) {
                    echo "    ├── {$test['test_number']} / compilation/execution error\n";
                    echo "        └── error: $actualOutput\n";
                    $wrongTotal++;
                    $wrongFolder++;
                    
                    // Salvar resultado no banco
                    $stmt = $pdo->prepare("
                        INSERT INTO test_results (submission_id, test_case_id, actual_output, is_correct, execution_time_ms, error_message) 
                        SELECT ?, id, '', false, ?, ? FROM test_cases 
                        WHERE challenge_id = ? AND folder_number = ? AND test_number = ?
                    ");
                    $stmt->execute([
                        $submissionId, 
                        $executionTime, 
                        $actualOutput,
                        $submission['challenge_id'], 
                        $test['folder_number'], 
                        $test['test_number']
                    ]);
                    continue;
                }

                // Compara as saídas
                $actualOutputClean = trim($actualOutput);
                $expectedOutputClean = trim($test['expected_output']);
                $isCorrect = ($actualOutputClean === $expectedOutputClean);

                if ($isCorrect) {
                    echo "    ├── {$test['test_number']} / right answer\n";
                    echo "        ├── out: $actualOutput";
                    echo "        └── answer: {$test['expected_output']}\n";
                    $correctTotal++;
                    $correctFolder++;
                } else {
                    echo "    ├── {$test['test_number']} / wrong answer\n";
                    echo "        ├── out: $actualOutput";
                    echo "        └── answer: {$test['expected_output']}\n";
                    $wrongTotal++;
                    $wrongFolder++;
                }
                
                // Salvar resultado no banco
                $stmt = $pdo->prepare("
                    INSERT INTO test_results (submission_id, test_case_id, actual_output, is_correct, execution_time_ms) 
                    SELECT ?, id, ?, ?, ? FROM test_cases 
                    WHERE challenge_id = ? AND folder_number = ? AND test_number = ?
                ");
                $stmt->execute([
                    $submissionId, 
                    $actualOutput, 
                    $isCorrect, 
                    $executionTime,
                    $submission['challenge_id'], 
                    $test['folder_number'], 
                    $test['test_number']
                ]);
            }
            
            echo "    └── statistics folder\n";
            echo "        ├── correct: $correctFolder\n";
            echo "        └── wrong: $wrongFolder\n";
        }

        echo "\n";
        echo "----------------------------------------------\n";
        echo "    └── Statistics Total\n";
        echo "        ├── correct: $correctTotal\n";
        echo "        ├── wrong: $wrongTotal\n";
        echo "        └── execution time: " . round($totalExecutionTime, 2) . "ms\n";
        
        // Atualizar execução
        $status = ($wrongTotal === 0) ? 'completed' : 'failed';
        $stmt = $pdo->prepare("
            UPDATE executions 
            SET passed_tests = ?, failed_tests = ?, total_execution_time_ms = ?, status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$correctTotal, $wrongTotal, $totalExecutionTime, $status, $executionId]);
        
        return true;
        
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}

/**
 * Executa teste completo: submete código e roda todos os testes
 */
function execFull($pdo, $filepath, $author = 'Sistema') {
    $pathInfo = pathinfo($filepath);
    $challengeName = $pathInfo['filename']; // Nome do arquivo sem extensão
    
    echo "🚀 Submetendo código para o desafio: $challengeName\n";
    echo str_repeat("=", 50) . "\n";
    
    $submissionId = submitCode($pdo, $challengeName, $filepath, $author);
    
    if (!$submissionId) {
        return false;
    }
    
    echo "\n🧪 Executando testes...\n";
    echo str_repeat("=", 50) . "\n";
    
    return runTests($pdo, $submissionId);
}

// Execução principal
if ($argc < 2) {
    echo "Uso: php run_db.php <arquivo_codigo> [autor]\n";
    echo "Exemplo: php run_db.php postes.py \"João Silva\"\n";
    exit(1);
}

$filepath = $argv[1];
$author = $argv[2] ?? 'Sistema';

if (!file_exists($filepath)) {
    echo "Arquivo não encontrado: $filepath\n";
    exit(1);
}

$pdo = connectDatabase($config);
execFull($pdo, $filepath, $author);
?>
