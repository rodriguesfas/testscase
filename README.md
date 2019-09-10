# Tests Case
A script to test cases for programming marathon algorithms like [OBI](https://olimpiada.ic.unicamp.br), [OCI](https://www.oci.org.br/), [URI Online Judge](www.urionlinejudge.com.br/judge/), [ICPC](https://icpc.baylor.edu/), among others.

# Usage Guide

## Dependencies
Python 3.7

## Install
Clone or download the repository to your computer.

    git clone https://github.com/rodriguesfas/testscase.git

## Folder Templates
- Add in the template folder your subfolder, template of the question you want to test the test cases.

- The folder name must be the same as your python file. Therefore, avoid using special characters.

## Run

    python run.py my_file.py

## Test

    # RUN
    python run.py postes.py

    # OUTPUT
    postes /
    │
    │
    ├── folder 1
        ├── 4 / right answer
            ├── out: 0 3
            └── answer: 0 3

        ├── 2 / right answer
            ├── out: 1 3
            └── answer: 1 3

        ├── 5 / right answer
            ├── out: 0 8
            └── answer: 0 8

        ├── 3 / right answer
            ├── out: 1 2
            └── answer: 1 2

        ├── 1 / right answer
            ├── out: 4 0
            └── answer: 4 0

        └── statistics folder
            ├── correct: 5
            └── wrong: 0
    │
    ├── folder 3
        ├── 4 / right answer
            ├── out: 14 65
            └── answer: 14 65

        ├── 2 / right answer
            ├── out: 0 100
            └── answer: 0 100

        ├── 5 / right answer
            ├── out: 49 300
            └── answer: 49 300

        ├── 3 / right answer
            ├── out: 44 30
            └── answer: 44 30

        ├── 6 / right answer
            ├── out: 147 162
            └── answer: 147 162

        ├── 1 / right answer
            ├── out: 100 0
            └── answer: 100 0

        ├── 7 / right answer
            ├── out: 215 101
            └── answer: 215 101

        └── statistics folder
            ├── correct: 7
            └── wrong: 0
    │
    ├── folder 4
        ├── 4 / right answer
            ├── out: 294 706
            └── answer: 294 706

        ├── 2 / right answer
            ├── out: 195 198
            └── answer: 195 198

        ├── 5 / right answer
            ├── out: 175 732
            └── answer: 175 732

        ├── 3 / right answer
            ├── out: 310 335
            └── answer: 310 335

        ├── 6 / right answer
            ├── out: 85 508
            └── answer: 85 508

        ├── 8 / right answer
            ├── out: 799 53
            └── answer: 799 53

        ├── 1 / right answer
            ├── out: 1000 0
            └── answer: 1000 0

        ├── 7 / right answer
            ├── out: 0 1000
            └── answer: 0 1000

        └── statistics folder
            ├── correct: 8
            └── wrong: 0
    │
    ├── folder 2
        ├── 4 / right answer
            ├── out: 8 19
            └── answer: 8 19

        ├── 2 / right answer
            ├── out: 9 6
            └── answer: 9 6

        ├── 5 / right answer
            ├── out: 0 0
            └── answer: 0 0

        ├── 3 / right answer
            ├── out: 17 3
            └── answer: 17 3

        ├── 1 / right answer
            ├── out: 10 0
            └── answer: 10 0

        └── statistics folder
            ├── correct: 5
            └── wrong: 0

    ----------------------------------------------
        └── Statistics Total
            ├── correct: 25
            └── wrong: 0
