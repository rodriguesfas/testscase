<?php
/**
 * Sistema de teste para códigos de maratona de programação
 * Equivalente ao run.py, mas implementado em PHP
 */

const PATH_ROOT = "templates/";

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
    try {
        switch ($language) {
            case 'python':
                return executePython($filepath, $inputData);
            case 'c':
            case 'cpp':
                return executeCCpp($filepath, $inputData, $language);
            case 'java':
                return executeJava($filepath, $inputData);
            case 'javascript':
                return executeJavaScript($filepath, $inputData);
            case 'go':
                return executeGo($filepath, $inputData);
            case 'rust':
                return executeRust($filepath, $inputData);
            case 'php':
                return executePHP($filepath, $inputData);
            default:
                return ["Linguagem '$language' não suportada", false];
        }
    } catch (Exception $e) {
        return ["Erro na execução: " . $e->getMessage(), false];
    }
}

/**
 * Executa código Python
 */
function executePython($filepath, $inputData) {
    $descriptorspec = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w']   // stderr
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

/**
 * Compila e executa código C/C++
 */
function executeCCpp($filepath, $inputData, $language) {
    $tempDir = sys_get_temp_dir() . '/codedojo_' . uniqid();
    mkdir($tempDir, 0777, true);
    
    $executable = $tempDir . '/program';
    $compiler = ($language === 'c') ? 'gcc' : 'g++';
    
    try {
        // Compilação
        $compileCmd = "$compiler -o \"$executable\" \"$filepath\" 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);
        
        if ($compileReturn !== 0) {
            return ["Erro de compilação: " . implode("\n", $compileOutput), false];
        }
        
        // Execução
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
        // Limpa arquivos temporários
        if (file_exists($executable)) {
            unlink($executable);
        }
        rmdir($tempDir);
    }
}

/**
 * Compila e executa código Java
 */
function executeJava($filepath, $inputData) {
    $tempDir = sys_get_temp_dir() . '/codedojo_' . uniqid();
    mkdir($tempDir, 0777, true);
    
    $filename = basename($filepath);
    $tempJava = $tempDir . '/' . $filename;
    copy($filepath, $tempJava);
    
    try {
        // Compilação
        $compileCmd = "javac \"$tempJava\" 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);
        
        if ($compileReturn !== 0) {
            return ["Erro de compilação: " . implode("\n", $compileOutput), false];
        }
        
        // Execução
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
        // Limpa arquivos temporários
        array_map('unlink', glob("$tempDir/*"));
        rmdir($tempDir);
    }
}

/**
 * Executa código JavaScript com Node.js
 */
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

/**
 * Executa código Go
 */
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

/**
 * Compila e executa código Rust
 */
function executeRust($filepath, $inputData) {
    $tempDir = sys_get_temp_dir() . '/codedojo_' . uniqid();
    mkdir($tempDir, 0777, true);
    
    $executable = $tempDir . '/program';
    
    try {
        // Compilação
        $compileCmd = "rustc -o \"$executable\" \"$filepath\" 2>&1";
        exec($compileCmd, $compileOutput, $compileReturn);
        
        if ($compileReturn !== 0) {
            return ["Erro de compilação: " . implode("\n", $compileOutput), false];
        }
        
        // Execução
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
        // Limpa arquivos temporários
        if (file_exists($executable)) {
            unlink($executable);
        }
        rmdir($tempDir);
    }
}

/**
 * Executa código PHP
 */
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
 * Executa todos os testes para um arquivo
 */
function execFull($filepath) {
    // Detecta a linguagem do arquivo
    $language = detectLanguage($filepath);
    
    // Obtém o nome base do arquivo (sem extensão) para a pasta de testes
    $pathInfo = pathinfo($filepath);
    $folder = $pathInfo['filename'];
    
    echo "$folder / (linguagem: $language)\n";
    echo "│\n";

    $correctTotal = 0;
    $wrongTotal = 0;
    
    $testPath = PATH_ROOT . $folder . '/';
    
    if (!is_dir($testPath)) {
        echo "Pasta de testes não encontrada: $testPath\n";
        return;
    }
    
    $folders = scandir($testPath);
    $folders = array_filter($folders, function($item) use ($testPath) {
        return $item !== '.' && $item !== '..' && is_dir($testPath . $item);
    });
    
    foreach ($folders as $folderTest) {
        echo "│\n";
        echo "├── folder $folderTest\n";

        $correctFolder = 0;
        $wrongFolder = 0;

        $testFiles = scandir($testPath . $folderTest);
        $inputFiles = array_filter($testFiles, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'in';
        });

        foreach ($inputFiles as $inputFile) {
            $number = pathinfo($inputFile, PATHINFO_FILENAME);
            
            // Lê o arquivo de entrada
            $inputFilePath = $testPath . $folderTest . '/' . $number . '.in';
            $inputData = file_get_contents($inputFilePath);

            // Lê a resposta esperada
            $outputFilePath = $testPath . $folderTest . '/' . $number . '.sol';
            if (!file_exists($outputFilePath)) {
                echo "    ├── $number / arquivo .sol não encontrado\n";
                $wrongTotal++;
                $wrongFolder++;
                continue;
            }
            
            $expectedOutput = file_get_contents($outputFilePath);

            // Executa o código na linguagem apropriada
            [$actualOutput, $success] = compileAndExecute($filepath, $inputData, $language);

            if (!$success) {
                echo "    ├── $number / compilation/execution error\n";
                echo "        └── error: $actualOutput\n";
                $wrongTotal++;
                $wrongFolder++;
                continue;
            }

            // Compara as saídas (removendo espaços em branco no final)
            $actualOutputClean = trim($actualOutput);
            $expectedOutputClean = trim($expectedOutput);

            if ($actualOutputClean === $expectedOutputClean) {
                echo "    ├── $number / right answer\n";
                echo "        ├── out: $actualOutput";
                echo "        └── answer: $expectedOutput\n";
                $correctTotal++;
                $correctFolder++;
            } else {
                echo "    ├── $number / wrong answer\n";
                echo "        ├── out: $actualOutput";
                echo "        └── answer: $expectedOutput\n";
                $wrongTotal++;
                $wrongFolder++;
            }
        }
        
        echo "    └── statistics folder\n";
        echo "        ├── correct: $correctFolder\n";
        echo "        └── wrong: $wrongFolder\n";
    }

    echo "\n";
    echo "----------------------------------------------\n";
    echo "    └── Statistics Total\n";
    echo "        ├── correct: $correctTotal\n";
    echo "        └── wrong: $wrongTotal\n";
}

// Execução principal
if ($argc < 2) {
    echo "Uso: php run.php <arquivo_codigo>\n";
    exit(1);
}

$filepath = $argv[1];

if (!file_exists($filepath)) {
    echo "Arquivo não encontrado: $filepath\n";
    exit(1);
}

execFull($filepath);
?>
