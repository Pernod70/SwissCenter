// freeexe.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"

#include <sstream>

typedef std::basic_string<TCHAR> TCharString;

enum
{
	OK = 0,
	INCORRECT_SYNTAX = 1,
	OUT_OF_MEMORY = 2,
	ERROR_LAUNCHING_PROCESS = 3
};

int ParseCommandLine(TCharString &commandLine);


int _tmain(int argc, _TCHAR* argv[])
{
	// Brief syntax check
	if(argc < 2)
	{
		std::cerr << "Syntax: FreeExe <cmd> [\"parameters\"]" << std::endl;
		return INCORRECT_SYNTAX;
	}

	// Parse the commandline
	TCharString commandLine;
	int retVal = ParseCommandLine(commandLine);
	if(retVal == INCORRECT_SYNTAX)
	{
		std::cerr << "Error parsing command, incorrect syntax?" << std::endl;
	}

	// Copy the command to a non-const buffer for the call into CreateProcess()
	LPTSTR pszCmd = new TCHAR[commandLine.length() + 1];
	if(!pszCmd)
	{
		std::cerr << "Error allocating memory for command line buffer" << std::endl;
		return OUT_OF_MEMORY;
	}

	ZeroMemory(pszCmd, commandLine.length() + 1);
	_tcscpy(pszCmd, commandLine.c_str());


	// Launch the new process
	DWORD dwCreateFlags = CREATE_NEW_CONSOLE | CREATE_NO_WINDOW;
	STARTUPINFO startupInfo;
	PROCESS_INFORMATION processInfo;
	
	ZeroMemory(&startupInfo, sizeof(STARTUPINFO));

	if(!CreateProcess(NULL, pszCmd, NULL, NULL, FALSE, dwCreateFlags, NULL, NULL, &startupInfo, &processInfo))
	{
		LPVOID pMessage = NULL;
		DWORD dwLastError = GetLastError();

		if(!FormatMessage(FORMAT_MESSAGE_ALLOCATE_BUFFER | FORMAT_MESSAGE_FROM_SYSTEM | FORMAT_MESSAGE_IGNORE_INSERTS,
			NULL, dwLastError, MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT), (LPTSTR)&pMessage, 0, NULL))
		{
			std::cerr << "Error creating process, error code: " << dwLastError << std::endl;
			std::cerr << "Error getting error string, error code: " << GetLastError() << std::endl;
		}
		else
		{
			std::cerr << "Error creating process: " << (LPTSTR)pMessage << std::endl;

			if(pMessage)
				LocalFree(pMessage);
		}

		retVal = ERROR_LAUNCHING_PROCESS;
	}
	else
	{
		// Don't care about the info we got back, close all of the handles
		CloseHandle(processInfo.hProcess);
		CloseHandle(processInfo.hThread);
	}

	if(pszCmd)
		delete [] pszCmd;

	return retVal;
}

int ParseCommandLine(TCharString &commandLine)
{
	TCharString::size_type cropFrom = TCharString::npos;
	commandLine = GetCommandLine();
	
	// Strip off the command from the command line, handles quoted commands or non quoted commands
	if(commandLine[0] == _T('"'))
	{
		// Find the last " char and remove up to there
		TCharString::size_type lastQuote = commandLine.find(_T('"'), 1);

		if(lastQuote != TCharString::npos)
		{
			cropFrom = lastQuote + 2;
		}
	}
	else
	{
		TCharString::size_type space = commandLine.find(_T(' '));

		if(space != TCharString::npos)
		{
			cropFrom = space + 1;
		}
	}

	if(cropFrom == TCharString::npos)
	{
		return INCORRECT_SYNTAX;
	}

	commandLine = commandLine.substr(cropFrom);
	
	return OK;
}