import io, os, sys

from contextlib import redirect_stdout

PATH_ROOT = "templates/"

def exec_full(filepath):
    
    global_namespace = {
        "__file__": filepath,
        "__name__": "__main__",
    }

    folder, _ = filepath.split(".")

    print(folder,'/')
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
            
            if(extension == "in"):
                input_tests = open(PATH_ROOT+folder+'/'+folder_test[0]+'/'+number+'.in', "r")
                sys.stdin = input_tests

                output_tests = open(PATH_ROOT+folder+'/'+folder_test[0]+'/'+number+'.sol', "r").read()
                sys.stdout.read = output_tests

                with open(filepath, 'rb') as file:
                    f = io.StringIO()
                    with redirect_stdout(f):
                        exec(compile(file.read(), filepath, 'exec'), global_namespace)

                    out = f.getvalue()

                    if(out == sys.stdout.read):
                        print("    ├──", number,"/ right answer")
                        print("        ├──", "out:", out, end="")
                        print("        └──", "answer:", sys.stdout.read)
                        correct_total+=1
                        correct_folder+=1
                    else:
                        print("    ├──", number,"/ wrong answer")
                        print("        ├──", "out:", out, end="")
                        print("        └──", "answer:", sys.stdout.read)
                        wrong_total+=1
                        wrong_folder+=1
        
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