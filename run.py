import io
import os
import sys
import subprocess
import tempfile
import shutil

from contextlib import redirect_stdout

PATH_ROOT = "templates/"


def detect_language(filepath):
    """Detecta a linguagem com base na extensão do arquivo"""
    ext = filepath.split('.')[-1].lower()
    language_map = {
        'py': 'python',
        'cpp': 'cpp',
        'c': 'c',
        'java': 'java',
        'js': 'javascript',
        'ts': 'typescript',
        'go': 'go',
        'rs': 'rust'
    }
    return language_map.get(ext, 'unknown')


def compile_and_execute(filepath, input_data, language):
    """Compila (se necessário) e executa código em diferentes linguagens"""
    try:
        if language == 'python':
            # Execução Python (método original)
            return execute_python(filepath, input_data)

        elif language in ['c', 'cpp']:
            # Compilação e execução C/C++
            return execute_c_cpp(filepath, input_data, language)

        elif language == 'java':
            # Compilação e execução Java
            return execute_java(filepath, input_data)

        elif language == 'javascript':
            # Execução JavaScript (Node.js)
            return execute_javascript(filepath, input_data)

        elif language == 'go':
            # Execução Go
            return execute_go(filepath, input_data)

        elif language == 'rust':
            # Compilação e execução Rust
            return execute_rust(filepath, input_data)

        else:
            return f"Linguagem '{language}' não suportada", False

    except Exception as e:
        return f"Erro na execução: {str(e)}", False


def execute_python(filepath, input_data):
    """Executa código Python (método original)"""
    global_namespace = {
        "__file__": filepath,
        "__name__": "__main__",
    }

    # Redireciona stdin para os dados de entrada
    old_stdin = sys.stdin
    sys.stdin = io.StringIO(input_data)

    try:
        with open(filepath, 'rb') as file:
            f = io.StringIO()
            with redirect_stdout(f):
                exec(compile(file.read(), filepath, 'exec'),
                     global_namespace)

            output = f.getvalue()
            return output, True
    finally:
        sys.stdin = old_stdin


def execute_c_cpp(filepath, input_data, language):
    """Compila e executa código C/C++"""
    with tempfile.TemporaryDirectory() as temp_dir:
        executable = os.path.join(temp_dir, "program")

        # Compilação
        compiler = 'gcc' if language == 'c' else 'g++'
        compile_result = subprocess.run(
            [compiler, '-o', executable, filepath],
            capture_output=True, text=True
        )

        if compile_result.returncode != 0:
            return f"Erro de compilação: {compile_result.stderr}", False

        # Execução
        exec_result = subprocess.run(
            [executable],
            input=input_data,
            capture_output=True, text=True
        )

        return exec_result.stdout, exec_result.returncode == 0


def execute_java(filepath, input_data):
    """Compila e executa código Java"""
    with tempfile.TemporaryDirectory() as temp_dir:
        # Copia arquivo Java para diretório temporário
        filename = os.path.basename(filepath)
        temp_java = os.path.join(temp_dir, filename)
        shutil.copy2(filepath, temp_java)

        # Compilação
        compile_result = subprocess.run(
            ['javac', temp_java],
            capture_output=True, text=True,
            cwd=temp_dir
        )

        if compile_result.returncode != 0:
            return f"Erro de compilação: {compile_result.stderr}", False

        # Execução
        class_name = filename.replace('.java', '')
        exec_result = subprocess.run(
            ['java', class_name],
            input=input_data,
            capture_output=True, text=True,
            cwd=temp_dir
        )

        return exec_result.stdout, exec_result.returncode == 0


def execute_javascript(filepath, input_data):
    """Executa código JavaScript com Node.js"""
    exec_result = subprocess.run(
        ['node', filepath],
        input=input_data,
        capture_output=True, text=True
    )

    return exec_result.stdout, exec_result.returncode == 0


def execute_go(filepath, input_data):
    """Executa código Go"""
    exec_result = subprocess.run(
        ['go', 'run', filepath],
        input=input_data,
        capture_output=True, text=True
    )

    return exec_result.stdout, exec_result.returncode == 0


def execute_rust(filepath, input_data):
    """Compila e executa código Rust"""
    with tempfile.TemporaryDirectory() as temp_dir:
        executable = os.path.join(temp_dir, "program")

        # Compilação
        compile_result = subprocess.run(
            ['rustc', '-o', executable, filepath],
            capture_output=True, text=True
        )

        if compile_result.returncode != 0:
            return f"Erro de compilação: {compile_result.stderr}", False

        # Execução
        exec_result = subprocess.run(
            [executable],
            input=input_data,
            capture_output=True, text=True
        )

        return exec_result.stdout, exec_result.returncode == 0


def exec_full(filepath):
    # Detecta a linguagem do arquivo
    language = detect_language(filepath)
    
    # Obtém o nome base do arquivo (sem extensão) para a pasta de testes
    folder = '.'.join(filepath.split('.')[:-1])
    if '/' in folder:
        folder = folder.split('/')[-1]  # Pega apenas o nome do arquivo

    print(f"{folder} / (linguagem: {language})")
    print('│')

    correct_total = 0
    wrong_total = 0
    
    for folder_test in os.listdir(PATH_ROOT+folder+'/'):
        print("│")
        print("├── folder", folder_test[0])

        correct_folder = 0
        wrong_folder = 0

        for file_test in os.listdir(PATH_ROOT+folder+'/'+folder_test[0]):
            number, extension = file_test.split(".")
            
            if extension == "in":
                # Lê o arquivo de entrada
                input_file_path = (PATH_ROOT+folder+'/'+folder_test[0]+'/' +
                                   number+'.in')
                with open(input_file_path, "r") as input_file:
                    input_data = input_file.read()

                # Lê a resposta esperada
                output_file_path = (PATH_ROOT+folder+'/'+folder_test[0]+'/' +
                                    number+'.sol')
                with open(output_file_path, "r") as output_file:
                    expected_output = output_file.read()

                # Executa o código na linguagem apropriada
                actual_output, success = compile_and_execute(filepath,
                                                             input_data,
                                                             language)

                if not success:
                    print("    ├──", number, "/ compilation/execution error")
                    print("        └──", "error:", actual_output)
                    wrong_total += 1
                    wrong_folder += 1
                    continue

                # Compara as saídas (removendo espaços em branco no final)
                actual_output_clean = actual_output.strip()
                expected_output_clean = expected_output.strip()

                if actual_output_clean == expected_output_clean:
                    print("    ├──", number, "/ right answer")
                    print("        ├──", "out:", actual_output, end="")
                    print("        └──", "answer:", expected_output)
                    correct_total += 1
                    correct_folder += 1
                else:
                    print("    ├──", number, "/ wrong answer")
                    print("        ├──", "out:", actual_output, end="")
                    print("        └──", "answer:", expected_output)
                    wrong_total += 1
                    wrong_folder += 1
        
        print("    └── statistics folder")
        print("        ├──", "correct:", correct_folder)
        print("        └──", "wrong:", wrong_folder)

    print("")
    print("----------------------------------------------")
    print("    └── Statistics Total")
    print("        ├──", "correct:", correct_total)
    print("        └──", "wrong:", wrong_total)


if __name__ == "__main__":
    filepath = sys.argv[1]
    exec_full(filepath)
