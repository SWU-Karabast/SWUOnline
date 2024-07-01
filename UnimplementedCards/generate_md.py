import argparse
import os
from pathlib import Path
import sys

README_FILE_NAME = "readme.md"
IGNORE_FILES_LIST = ["generate_md.py", README_FILE_NAME]

def list_image_files(root_dir: Path) -> list[Path]:
    image_files: list[Path] = []
    for file_path in root_dir.iterdir():
        if file_path.name.lower() in IGNORE_FILES_LIST or file_path.is_dir():
            continue

        if file_path.suffix != ".webp":
            raise RuntimeError(f"Unexpected file '{file_path}' found while generating unimplemented files readme. Please remove the file or add it to the ignore list in generate_md.py")
        
        image_files.append(file_path)
    
    return image_files

def generate_md(image_files: list[Path]):
    readme_text = """# Cards yet to be Implemented
The images below show cards that are _**not yet**_ implemented. If you are having issues with a card that is not on the list, please reach out in the discord or create a github issue.
"""
    for webm_file in image_files:
        readme_text += f"\n![](./{webm_file.name})"

    return readme_text

def validate_md(root_dir: Path, image_files: list[Path], verbose: bool):
    expected_md_file = generate_md(image_files)
    with open(root_dir / README_FILE_NAME, "r") as f:
        actual_md_file = f.read()

    if verbose:
        print("\n------EXPECTED MD FILE CONTENTS------")
        print(expected_md_file)
        print("\n\n------ACTUAL MD FILE CONTENTS------")
        print(actual_md_file)
        print("\n")

    if expected_md_file != actual_md_file:
        print("Unimplemented cards readme file is out of date and does not match the contents of the folder './UnimplementedCards/'. Please run `python ./UnimplementedCards/generate_md.py generate` to update it.", file=sys.stderr)
        sys.exit(1)
    
    print("Unimplemented cards readme validation passed")

def save_md(save_dir: Path, image_files: list[Path]):
    with open(save_dir / README_FILE_NAME, "w") as f:
        f.write(generate_md(image_files))
    
    print(f"Unimplemented cards readme file saved to '{save_dir / README_FILE_NAME}'")

if __name__ == "__main__":
    parser = argparse.ArgumentParser("Regenerates or validates the readme.md file displaying the unimplemented card images")
    subparsers = parser.add_subparsers(dest="command")

    # process the 'generate' verb
    generate_parser = subparsers.add_parser("generate")

    # process the 'validate' verb
    validate_parser = subparsers.add_parser("validate")
    validate_parser.add_argument("--verbose", action="store_true", help="Log the contents of the expected and actual md files")

    args = parser.parse_args()

    # note: this will no longer be valid if this file is moved
    unimplemented_cards_dir = Path(os.path.dirname(os.path.realpath(__file__)))
    image_files = list_image_files(unimplemented_cards_dir)

    if args.command == "generate":
        save_md(unimplemented_cards_dir, image_files)
    else:
        validate_md(unimplemented_cards_dir, image_files, args.verbose)
