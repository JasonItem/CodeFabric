export type TableListRow = {
  tableName: string
  tableComment: string | null
  engine: string | null
  tableRows: number
  dataLength: number
  indexLength: number
  createTime: string | null
  updateTime: string | null
  collation: string | null
}

export type TableListResponse = {
  list: TableListRow[]
  total: number
}

export type TableColumnRow = {
  columnName: string
  columnType: string
  dataType: string
  isNullable: 'YES' | 'NO'
  columnDefault: string | null
  columnComment: string | null
  columnKey: string | null
  extra: string | null
  characterSetName: string | null
  collationName: string | null
  ordinalPosition: number
}

export type CreateSqlResponse = {
  tableName: string
  createSql: string
}

export type TableIndexRow = {
  indexName: string
  unique: boolean
  indexType: string
  indexComment: string | null
  columns: Array<{
    columnName: string
    seqInIndex: number
    subPart: number | null
    collation: string | null
  }>
}

export type TableForeignKeyRow = {
  constraintName: string
  columnName: string
  referencedTableName: string
  referencedColumnName: string
  updateRule: string
  deleteRule: string
}

export type TableExportResponse = {
  tableName: string
  fileName: string
  sql: string
}

export type TableExportAllResponse = {
  fileName: string
  sql: string
  tableCount: number
}

export type TableImportResponse = {
  count: number
  skippedCount: number
  skippedTables: string[]
  mode: 'strict' | 'skip-create'
  fileName: string
}
