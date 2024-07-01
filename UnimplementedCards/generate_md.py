from pathlib import Path
import os
import argparse

IGNORE_FILES_LIST = ["generate_md.py", "readme.md"]

def list_webm_files(root_dir: Path) -> list[Path]:
    webm_files: list[Path] = []
    for file_path in root_dir.iterdir():
        if file_path.suffix != ".webm" and file_path.name.tolower() not in IGNORE_FILES_LIST:
            raise RuntimeError(f"Unexpected file '{file_path}' found while generating unimplemented files readme. Please remove the file or add it to the ignore list in generate_md.py")
        
        webm_files.append(file_path)
    
    return webm_files

def generate_md(webm_files: list[Path]):
    readme_text = """# Cards yet to be Implemented
The images below show cards that are _**not yet**_ implemented. If you are having issues with a card that is not on the list, please reach out in the discord or create a github issue.
"""
    for webm_file in webm_files:
        readme_text += f"\n![](./{webm_file.name})"



def validate_md(webm_files: list[Path]):
    expected_md_file = generate_md(webm_files)

if __name__ == "__main__":
    parser = argparse.ArgumentParser("Regenerates or validates the readme.md file displaying the unimplemented card images")
    arg_group = parser.add_mutually_exclusive_group(required=True)
    arg_group.add_argument("--generate", action="store_true")
    arg_group.add_argument("--validate", action="store_true")
    args = parser.parse_args()

    # note: this will no longer be valid if this file is moved
    unimplemented_cards_dir = Path(os.path.dirname(os.path.realpath(__file__)))
    webm_files = list_webm_files(unimplemented_cards_dir)

    if args.generate:
        generate_md(unimplemented_cards_dir)
    else:
        validate_md(unimplemented_cards_dir)
