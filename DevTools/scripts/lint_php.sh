# this script lints all php files in the repo using 'php -l', and returns nonzero if there are any errors

RED_TEXT='\033[1;31m'
GREEN_TEXT='\033[1;32m'
RESET_TEXT='\033[0m'

PARENT_DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# allow globbing subdirectories for search
shopt -s globstar

(cd $PARENT_DIR/../../
linting_passed=true
for i in **/*.php; do
    # capture output so we can display in red on failure
    out=$((php -l "$i") 2>&1)
    
    # check success of php -l command
    if [[ $? -eq 0 ]]; then
        echo ${out}
    else
        linting_passed=false
        echo -e "${RED_TEXT}${out}${RESET_TEXT}"
    fi
done
)

if [[ $linting_passed == true ]]; then
    echo -e "\n${GREEN_TEXT}PHP linting succeeded${RESET_TEXT}"
    exit 0
else
    echo -e "\n${RED_TEXT}PHP linting failed, please fix indicated files${RESET_TEXT}"
    exit 1
fi